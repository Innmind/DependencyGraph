<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader\Dependents;

use Innmind\DependencyGraph\{
    Package,
    Package\Name,
};
use Innmind\Immutable\Set;

final class Graph
{
    private function __construct()
    {
    }

    /**
     * @param Set<Package> $packages
     *
     * @return Set<Package>
     */
    public static function of(Package $root, Set $packages): Set
    {
        $packages = $packages
            ->add($root)
            ->filter(
                static fn($dependent) => self::dependsOn($root, $dependent, $packages),
            );
        $names = $packages->map(static fn($package) => $package->name());

        return $packages->map(static fn($package) => $package->keep($names));
    }

    /**
     * @param Set<Package> $packages
     */
    private static function dependsOn(
        Package $root,
        Package $package,
        Set $packages,
    ): bool {
        if ($package->name()->equals($root->name())) {
            return true;
        }

        if ($package->dependsOn($root->name())) {
            return true;
        }

        return $package->relations()->any(
            static fn($relation) => $packages
                ->find(static fn($package) => $package->name()->equals($relation->name()))
                ->match(
                    static fn($package) => self::dependsOn($root, $package, $packages),
                    static fn() => false,
                ),
        );
    }
}
