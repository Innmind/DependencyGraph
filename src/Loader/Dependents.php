<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Dependents\Graph,
    Package,
    Vendor as Model,
};
use Innmind\Immutable\Set;

final class Dependents
{
    private Vendor $load;

    public function __construct(Vendor $load)
    {
        $this->load = $load;
    }

    /**
     * @param Set<Model\Name> $vendors
     *
     * @return Set<Package>
     */
    public function __invoke(Package\Name $name, Set $vendors): Set
    {
        $packages = $vendors
            ->map(fn(Model\Name $vendor): Model => ($this->load)($vendor))
            ->flatMap(static fn($vendor) => $vendor->packages());

        return $packages
            ->find(static fn($package) => $package->name()->equals($name))
            ->match(
                static fn($package) => Graph::of(
                    $package,
                    $packages->filter(
                        static fn($package) => !$package->name()->equals($name),
                    ),
                ),
                static fn() => Set::of(),
            );
    }
}
