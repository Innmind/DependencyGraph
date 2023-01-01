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

        $version = $this->mostRecentVersion($content['versions']);
        $relations = $version->map($this->loadRelations(...));
        $abandoned = $version->map(static fn($version) => ($version['abandoned'] ?? false) !== false);
        $version = $version
            ->map(static fn($version) => $version['version'])
            ->flatMap(Model\Version::maybe(...));

        /** @psalm-suppress MixedArgumentTypeCoercion */
        return Maybe::all($version, $relations, $abandoned)
            ->map(static fn(Model\Version $version, Set $relations, bool $abandoned) => new Model(
                $name,
                $version,
                Url::of("https://packagist.org/packages/{$name->toString()}"),
                $relations,
                $abandoned,
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

        $published = $published->filter(static function(string $version): bool {
            return VersionParser::parseStability($version) === 'stable';
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
            $relations = Maybe::all(Model\Name::maybe($relation), Model\Constraint::maybe($constraint))
                ->map(Model\Relation::of(...))
                ->match(
                    static fn($relation) => ($relations)($relation),
                    static fn() => $relations,
                );
        }

        return $relations;
    }
}
