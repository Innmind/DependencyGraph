<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Package as PackageModel,
    Package\Relation,
    Vendor as VendorModel,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
    MapInterface,
    Map,
};

final class VendorDependencies
{
    private $loadVendor;
    private $loadPackage;

    public function __construct(
        Vendor $loadVendor,
        Package $loadPackage
    ) {
        $this->loadVendor = $loadVendor;
        $this->loadPackage = $loadPackage;
    }

    /**
     * @return SetInterface<PackageModel>
     */
    public function __invoke(VendorModel\Name $name): SetInterface
    {
        $vendor = ($this->loadVendor)($name);
        $packages = Set::of(PackageModel::class, ...$vendor)->reduce(
            Map::of('string', PackageModel::class),
            static function(MapInterface $packages, PackageModel $package): MapInterface {
                return $packages->put(
                    (string) $package->name(),
                    $package
                );
            }
        );
        $packages = $packages->reduce(
            $packages,
            function(MapInterface $packages, string $name, PackageModel $package): MapInterface {
                return $this->load($package, $packages);
            }
        );

        return Set::of(PackageModel::class, ...$packages->values());
    }

    private function load(PackageModel $package, MapInterface $packages): MapInterface
    {
        return $package->relations()->reduce(
            $packages,
            function(MapInterface $packages, Relation $relation): MapInterface {
                if ($packages->contains((string) $relation->name())) {
                    return $packages;
                }

                $relation = ($this->loadPackage)($relation->name());

                return $this->load(
                    $relation,
                    $packages->put(
                        (string) $relation->name(),
                        $relation
                    )
                );
            }
        );
    }
}
