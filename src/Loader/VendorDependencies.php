<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Package as PackageModel,
    Package\Relation,
    Vendor as VendorModel,
    Exception\NoPublishedVersion,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
    MapInterface,
    Map,
};

final class VendorDependencies
{
    private Vendor $loadVendor;
    private Package $loadPackage;

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
        $names = $packages->keys()->reduce(
            Set::of(PackageModel\Name::class),
            static function(SetInterface $names, string $name): SetInterface {
                return $names->add(PackageModel\Name::of($name));
            }
        );

        return Set::of(
            PackageModel::class,
            ...$packages
                ->values()
                ->map(static function(PackageModel $package) use ($names): PackageModel {
                    return $package->keep(...$names); // remove relations with no stable releases
                })
        );
    }

    private function load(PackageModel $package, MapInterface $packages): MapInterface
    {
        return $package->relations()->reduce(
            $packages,
            function(MapInterface $packages, Relation $relation): MapInterface {
                if ($packages->contains((string) $relation->name())) {
                    return $packages;
                }

                try {
                    $relation = ($this->loadPackage)($relation->name());
                } catch (NoPublishedVersion $e) {
                    return $packages;
                }

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
