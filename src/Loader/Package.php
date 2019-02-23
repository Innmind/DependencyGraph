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

final class Package
{
    private $fulfill;

    public function __construct(Transport $fulfill)
    {
        $this->fulfill = $fulfill;
    }

    /**
     * @throws NoPublishedVersion;
     */
    public function __invoke(Model\Name $name): Model
    {
        $request = new Request(
            Url::fromString("https://packagist.org/packages/$name.json"),
            Method::get(),
            new ProtocolVersion(2, 0)
        );
        $response = ($this->fulfill)($request);
        $content = Json::decode((string) $response->body())['package'];

        $version = $this->mostRecentVersion($content['versions']);
        $relations = $this->loadRelations($version);

        return new Model(
            Model\Name::of($content['name']),
            new Model\Version($version['version']),
            Url::fromString("https://packagist.org/packages/$name"),
            ...$relations
        );
    }

    private function mostRecentVersion(array $versions): array
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

        return $published->current();
    }

    /**
     * @return SetInterface<Model\Relation>
     */
    private function loadRelations(array $version): SetInterface
    {
        $relations = [];

        foreach ($version['require'] ?? [] as $relation => $_) {
            if (!Str::of($relation)->matches('~.+/.+~')) {
                continue;
            }

            $relations[] = new Model\Relation(
                Model\Name::of($relation)
            );
        }

        return Set::of(Model\Relation::class, ...$relations);
    }
}
