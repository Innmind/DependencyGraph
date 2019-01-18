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
            $url = Url::fromString("https://packagist.org/packages/$name.json"),
            Method::get(),
            new ProtocolVersion(2, 0)
        );
        $response = ($this->fulfill)($request);
        $content = Json::decode((string) $response->body())['package'];

        $relations = $this->loadRelations($content['versions']);

        return new Model(
            Model\Name::of($content['name']),
            $url,
            Url::fromString($content['repository']),
            ...$relations
        );
    }

    /**
     * @return SetInterface<Model\Relation>
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

            $relations[] = new Model\Relation(
                Model\Name::of($relation)
            );
        }

        return Set::of(Model\Relation::class, ...$relations);
    }
}
