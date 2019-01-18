<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Dependents\Graph,
    Package as Model,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
    MapInterface,
    Map,
};

final class Dependencies
{
    private $load;

    public function __construct(Package $load)
    {
        $this->load = $load;
    }

    /**
     * @return SetInterface<Model>
     */
    public function __invoke(Model\Name $name): SetInterface
    {
        $packages = $this->load($name, Map::of('string', Model::class));

        return Set::of(Model::class, ...$packages->values());
    }

    private function load(Model\Name $name, MapInterface $packages): MapInterface
    {
        if ($packages->contains((string) $name)) {
            return $packages;
        }

        $package = ($this->load)($name);

        return $package->relations()->reduce(
            $packages->put((string) $name, $package),
            function(MapInterface $packages, Model\Relation $relation): MapInterface {
                return $this->load($relation->name(), $packages);
            }
        );
    }
}
