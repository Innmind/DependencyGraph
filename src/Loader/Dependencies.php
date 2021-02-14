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

        /** @var Set<Model> */
        return $packages->values()->toSetOf(Model::class);
    }

    private function load(Model\Name $name, Map $packages): Map
    {
        if ($packages->contains($name->toString())) {
            return $packages;
        }

        $package = ($this->load)($name);

        return $package->relations()->reduce(
            ($packages)($name->toString(), $package),
            function(Map $packages, Model\Relation $relation): Map {
                return $this->load($relation->name(), $packages);
            },
        );
    }
}
