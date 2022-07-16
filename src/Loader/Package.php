<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\Package as Model;
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method,
    ProtocolVersion,
};
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Immutable\{
    Set,
    Map,
    Str,
    Sequence,
    Maybe,
};
use Composer\Semver\{
    VersionParser,
    Semver,
};

/**
 * @psalm-type Definition = array{version: string, abandoned?: bool, require?: array<string, string>}
 */
final class Package
{
    private Transport $fulfill;

    public function __construct(Transport $fulfill)
    {
        $this->fulfill = $fulfill;
    }

    /**
     * @return Maybe<Model>
     */
    public function __invoke(Model\Name $name): Maybe
    {
        $request = new Request(
            Url::of("https://packagist.org/packages/{$name->toString()}.json"),
            Method::get,
            ProtocolVersion::v20,
        );
        $response = ($this->fulfill)($request)->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );
        /** @var array{package: array{name: string, versions: array<string, Definition>}} */
        $body = Json::decode($response->body()->toString());
        $content = $body['package'];

        return $this
            ->mostRecentVersion($content['versions'])
            ->map(fn($version) => new Model(
                Model\Name::of($content['name']),
                new Model\Version($version['version']),
                Url::of("https://packagist.org/packages/{$name->toString()}"),
                $this->loadRelations($version),
            ));
    }

    /**
     * @param array<string, Definition> $versions
     *
     * @return Maybe<Definition>
     */
    private function mostRecentVersion(array $versions): Maybe
    {
        /** @var Map<string, Definition> */
        $published = Map::of();

        foreach ($versions as $key => $value) {
            $published = ($published)($key, $value);
        }

        $published = $published
            ->filter(static function(string $version): bool {
                return VersionParser::parseStability($version) === 'stable';
            })
            ->filter(static function(string $_, array $version): bool {
                return !($version['abandoned'] ?? false);
            });

        $versions = Sequence::of(...\array_values(Semver::rsort($published->keys()->toList())));

        /** @var Maybe<Definition> */
        return $versions
            ->first()
            ->flatMap(static fn($version) => $published->get($version));
    }

    /**
     * @param Definition $version
     *
     * @return Set<Model\Relation>
     */
    private function loadRelations(array $version): Set
    {
        /** @var Set<Model\Relation> */
        $relations = Set::of();

        foreach ($version['require'] ?? [] as $relation => $constraint) {
            if (!Str::of($relation)->matches('~.+/.+~')) {
                continue;
            }

            $relations = ($relations)(new Model\Relation(
                Model\Name::of($relation),
                new Model\Constraint($constraint),
            ));
        }

        return $relations;
    }
}
