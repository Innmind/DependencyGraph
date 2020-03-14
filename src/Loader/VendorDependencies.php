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
    Set,
    Map,
};
use function Innmind\Immutable\unwrap;

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
     * @return Set<PackageModel>
     */
    public function __invoke(VendorModel\Name $name): Set
    {
        $vendor = ($this->loadVendor)($name);
        /** @var Map<string, PackageModel> */
        $packages = $vendor->packages()->toMapOf(
            'string',
            PackageModel::class,
            static function(PackageModel $package): \Generator {
                yield $package->name()->toString() => $package;
            },
        );
        $packages = $packages->reduce(
            $packages,
            function(Map $packages, string $name, PackageModel $package): Map {
                return $this->load($package, $packages);
            }
        );
        $names = $packages->keys()->mapTo(
            PackageModel\Name::class,
            static fn(string $name): PackageModel\Name => PackageModel\Name::of($name),
        );

        $dependencies = $packages
            ->values()
            ->map(static function(PackageModel $package) use ($names): PackageModel {
                return $package->keep(...unwrap($names)); // remove relations with no stable releases
            });

        return Set::of(
            PackageModel::class,
            ...unwrap($dependencies),
        );
    }

    private function load(PackageModel $package, Map $packages): Map
    {
        return $package->relations()->reduce(
            $packages,
            function(Map $packages, Relation $relation): Map {
                if ($packages->contains($relation->name()->toString())) {
                    return $packages;
                }

                try {
                    $relation = ($this->loadPackage)($relation->name());
                } catch (NoPublishedVersion $e) {
                    return $packages;
                }

                return $this->load(
                    $relation,
                    ($packages)(
                        $relation->name()->toString(),
                        $relation,
                    ),
                );
            },
        );
    }
}
