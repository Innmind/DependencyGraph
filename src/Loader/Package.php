<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\Package as Model;
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Request,
    Response,
    Method,
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
        $request = Request::of(
            Url::of("https://packagist.org/packages/{$name->toString()}.json"),
            Method::get,
            ProtocolVersion::v20,
        );

        return ($this->fulfill)($request)
            ->maybe()
            ->flatMap(fn($success) => $this->parse($success->response(), $name));
    }

    /**
     * @return Maybe<Model>
     */
    private function parse(Response $response, Model\Name $name): Maybe
    {
        /** @var array{
         *      package: array{
         *          name: string,
         *          versions: array<array-key, Definition>,
         *          repository: non-empty-string,
         *      }
         *  }
         */
        $body = Json::decode($response->body()->toString());
        $content = $body['package'];

        $version = $this->mostRecentVersion($content['versions']);
        $relations = $version->map($this->loadRelations(...));
        $abandoned = $version->map(static fn($version) => ($version['abandoned'] ?? false) !== false);
        $version = $version
            ->map(static fn($version) => $version['version'])
            ->flatMap(Model\Version::maybe(...));
        $repository = Url::maybe(\rtrim($content['repository'], '/').'/');

        /** @psalm-suppress MixedArgumentTypeCoercion */
        return Maybe::all($version, $relations, $abandoned, $repository)
            ->map(static fn(
                Model\Version $version,
                Set $relations,
                bool $abandoned,
                Url $repository,
            ) => new Model(
                $name,
                $version,
                Url::of("https://packagist.org/packages/{$name->toString()}"),
                $repository,
                $relations,
                $abandoned,
            ));
    }

    /**
     * @param array<array-key, Definition> $versions
     *
     * @return Maybe<Definition>
     */
    private function mostRecentVersion(array $versions): Maybe
    {
        /** @var Map<string, Definition> */
        $published = Map::of();

        foreach ($versions as $key => $value) {
            $published = ($published)((string) $key, $value);
        }

        $published = $published->filter(static function(string $version): bool {
            return VersionParser::parseStability($version) === 'stable';
        });

        /** @var Sequence<string> */
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
