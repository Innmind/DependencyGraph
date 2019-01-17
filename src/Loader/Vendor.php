<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Package,
    Vendor as Model,
    Exception\NoPublishedVersion,
};
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method\Method,
    ProtocolVersion\ProtocolVersion,
};
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Immutable\{
    SetInterface,
    Set,
    Map,
    Str,
};

final class Vendor
{
    private $fulfill;

    public function __construct(Transport $fulfill)
    {
        $this->fulfill = $fulfill;
    }

    public function __invoke(Model\Name $name): Model
    {
        $url = "https://packagist.org/search.json?q=$name/";
        $results = [];

        do {
            $request = new Request(
                Url::fromString($url),
                Method::get(),
                new ProtocolVersion(2, 0)
            );
            $response = ($this->fulfill)($request);
            $content = Json::decode((string) $response->body());
            $results = \array_merge($results, $content['results']);
            $url = $content['next'] ?? null;
        } while (isset($content['next']));

        $packages = [];

        foreach ($results as $result) {
            if (!Str::of($result['name'])->matches("~^$name/~")) {
                continue;
            }

            if ($result['virtual'] ?? false === true) {
                continue;
            }

            try {
                $packages[] = $this->loadPackage($result['url']);
            } catch (NoPublishedVersion $e) {
                // do not expose the package if no tag found
            }
        }

        return new Model(...$packages);
    }

    private function loadPackage(string $url): Package
    {
        $request = new Request(
            Url::fromString($url.'.json'),
            Method::get(),
            new ProtocolVersion(2, 0)
        );
        $response = ($this->fulfill)($request);
        $content = Json::decode((string) $response->body())['package'];

        $relations = $this->loadRelations($content['versions']);

        return new Package(
            Package\Name::of($content['name']),
            Url::fromString($url),
            Url::fromString($content['repository']),
            ...$relations
        );
    }

    /**
     * @return SetInterface<Package\Relation>
     */
    private function loadRelations(array $versions): SetInterface
    {
        $published = Map::of(
            'string',
            'array',
            \array_keys($versions),
            \array_values($versions)
        )
            ->filter(static function(string $version): bool {
                return Str::of($version)->take(4) !== 'dev-';
            })
            ->filter(static function(string $_, array $version): bool {
                return !($version['abandoned'] ?? false);
            });

        if ($published->size() === 0) {
            throw new NoPublishedVersion;
        }

        $version = $published->current();
        $relations = [];

        foreach ($version['require'] ?? [] as $relation => $_) {
            if (!Str::of($relation)->matches('~.+/.+~')) {
                continue;
            }

            $relations[] = new Package\Relation(
                Package\Name::of($relation)
            );
        }

        return Set::of(Package\Relation::class, ...$relations);
    }
}
