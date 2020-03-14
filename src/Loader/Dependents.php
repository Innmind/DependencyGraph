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
use function Innmind\Immutable\unwrap;

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
        Model\Name ...$vendors
    ): Set {
        $packages = Set::of(Model\Name::class, $required, ...$vendors)
            ->reduce(
                Set::of(Model::class),
                function(Set $vendors, Model\Name $vendor): Set {
                    return $vendors->add(($this->load)($vendor));
                }
            )
            ->reduce(
                Set::of(Package::class),
                static function(Set $packages, Model $vendor): Set {
                    return $packages->merge(Set::of(Package::class, ...$vendor));
                }
            )
            ->reduce(
                Map::of('string', Package::class),
                static function(Map $packages, Package $package): Map {
                    return $packages->put(
                        (string) $package->name(),
                        $package
                    );
                }
            );

        $name = (string) $name;

        if (!$packages->contains($name)) {
            return Set::of(Package::class);
        }

        return Graph::of(
            $packages->get($name),
            ...unwrap($packages
                ->remove($name)
                ->values())
        );
    }
}
