<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Dependents\Graph,
    Package as Model,
};
use Innmind\Immutable\{
    Set,
    Map,
};
use function Innmind\Immutable\unwrap;

final class Dependencies
{
    private Package $load;

    public function __construct(Package $load)
    {
        $this->load = $load;
    }

    /**
     * @return Set<Model>
     */
    public function __invoke(Model\Name $name): Set
    {
        $packages = $this->load($name, Map::of('string', Model::class));

        return Set::of(Model::class, ...unwrap($packages->values()));
    }

    private function load(Model\Name $name, Map $packages): Map
    {
        if ($packages->contains((string) $name)) {
            return $packages;
        }

        $package = ($this->load)($name);

        return $package->relations()->reduce(
            $packages->put((string) $name, $package),
            function(Map $packages, Model\Relation $relation): Map {
                return $this->load($relation->name(), $packages);
            }
        );
    }
}
