<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Dependents\Graph,
    Package,
    Vendor as Model,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
    MapInterface,
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
     * @return SetInterface<Package>
     */
    public function __invoke(
        Package\Name $name,
        Model\Name $required,
        Model\Name ...$vendors
    ): SetInterface {
        $packages = Set::of(Model\Name::class, $required, ...$vendors)
            ->reduce(
                Set::of(Model::class),
                function(SetInterface $vendors, Model\Name $vendor): SetInterface {
                    return $vendors->add(($this->load)($vendor));
                }
            )
            ->reduce(
                Set::of(Package::class),
                static function(SetInterface $packages, Model $vendor): SetInterface {
                    return $packages->merge(Set::of(Package::class, ...$vendor));
                }
            )
            ->reduce(
                Map::of('string', Package::class),
                static function(MapInterface $packages, Package $package): MapInterface {
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
            ...$packages
                ->remove($name)
                ->values()
        );
    }
}
