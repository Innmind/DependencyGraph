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
        $packages = $this->load(Map::of(), $name);

        return Set::of(...$packages->values()->toList());
    }

    /**
     * @param Map<string, Model> $packages
     *
     * @return Map<string, Model>
     */
    private function load(Map $packages, Model\Name $name): Map
    {
        if ($packages->contains($name->toString())) {
            return $packages;
        }

        $package = ($this->load)($name);

        return $package
            ->relations()
            ->map(static fn($relation) => $relation->name())
            ->reduce(
                ($packages)($name->toString(), $package),
                $this->load(...),
            );
    }
}
