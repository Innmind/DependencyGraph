<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Dependents\Graph,
    Package,
    Vendor as Model,
};
use Innmind\Immutable\{
    Set,
    Map,
};

final class Dependents
{
    private Vendor $load;

    public function __construct(Vendor $load)
    {
        $this->load = $load;
    }

    /**
     * @return Set<Package>
     */
    public function __invoke(
        Package\Name $name,
        Model\Name $required,
        Model\Name ...$vendors,
    ): Set {
        $vendors = Set::of($required, ...$vendors);

        $packages = $vendors
            ->map(fn(Model\Name $vendor): Model => ($this->load)($vendor))
            ->flatMap(static fn($vendor) => $vendor->packages());
        /** @var Map<string, Package> */
        $packages = $packages->reduce(
            Map::of(),
            static function(Map $packages, Package $package): Map {
                /** @var Map<string, Package> $packages */

                return ($packages)(
                    $package->name()->toString(),
                    $package,
                );
            },
        );

        $name = $name->toString();

        return $packages
            ->get($name)
            ->match(
                static fn($package) => Graph::of(
                    $package,
                    ...$packages->remove($name)->values()->toList(),
                ),
                static fn() => Set::of(),
            );
    }
}
