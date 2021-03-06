<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Package as Model,
    Exception\NoPublishedVersion,
};
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
};
use function Innmind\Immutable\unwrap;
use Composer\Semver\{
    VersionParser,
    Semver,
};

final class Package
{
    private Transport $fulfill;

    public function __construct(Transport $fulfill)
    {
        $this->fulfill = $fulfill;
    }

    /**
     * @throws NoPublishedVersion
     */
    public function __invoke(Model\Name $name): Model
    {
        $request = new Request(
            Url::of("https://packagist.org/packages/{$name->toString()}.json"),
            Method::get(),
            new ProtocolVersion(2, 0),
        );
        $response = ($this->fulfill)($request);
        /** @var array{package: array{name: string, versions: array<string, array{version: string, abandoned?: bool, require?: array<string, string>}>}} */
        $body = Json::decode($response->body()->toString());
        $content = $body['package'];

        $version = $this->mostRecentVersion($content['versions']);
        $relations = $this->loadRelations($version);

        return new Model(
            Model\Name::of($content['name']),
            new Model\Version($version['version']),
            Url::of("https://packagist.org/packages/{$name->toString()}"),
            ...unwrap($relations),
        );
    }

    /**
     * @param array<string, array{version: string, abandoned?: bool, require?: array<string, string>}> $versions
     *
     * @return array{version: string, abandoned?: bool,require?: array<string, string>}
     */
    private function mostRecentVersion(array $versions): array
    {
        /** @var Map<string, array{version: string, abandoned?: bool, require?: array<string, string>}> */
        $published = Map::of('string', 'array');

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

        if ($published->empty()) {
            throw new NoPublishedVersion;
        }

        /** @var list<string> */
        $versions = Semver::rsort(unwrap($published->keys()));

        return $published->get($versions[0]);
    }

    /**
     * @param array{version: string, abandoned?: bool, require?: array<string, string>} $version
     *
     * @return Set<Model\Relation>
     */
    private function loadRelations(array $version): Set
    {
        $relations = [];

        foreach ($version['require'] ?? [] as $relation => $constraint) {
            if (!Str::of($relation)->matches('~.+/.+~')) {
                continue;
            }

            $relations[] = new Model\Relation(
                Model\Name::of($relation),
                new Model\Constraint($constraint),
            );
        }

        return Set::of(Model\Relation::class, ...$relations);
    }
}
