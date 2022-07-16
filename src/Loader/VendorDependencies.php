<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Package as PackageModel,
    Package\Relation,
    Vendor as VendorModel,
};
use Innmind\Immutable\{
    Set,
    Map,
    Sequence,
};

final class VendorDependencies
{
    private Vendor $loadVendor;
    private Package $loadPackage;

    public function __construct(
        Vendor $loadVendor,
        Package $loadPackage,
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
        $packages = Map::of(
            ...$vendor
                ->packages()
                ->map(static fn($package) => [$package->name()->toString(), $package])
                ->toList(),
        );
        $packages = $packages
            ->values()
            ->reduce(
                $packages,
                $this->load(...),
            );
        $names = $packages->keys()->map(
            static fn(string $name): PackageModel\Name => PackageModel\Name::of($name),
        );

        $dependencies = $packages
            ->values()
            ->map(static function(PackageModel $package) use ($names): PackageModel {
                return $package->keep($names); // remove relations with no stable releases
            });

        return Set::of(...$dependencies->toList());
    }

    /**
     * @param Map<string, PackageModel> $packages
     *
     * @return Map<string, PackageModel>
     */
    private function load(Map $packages, PackageModel $package): Map
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return $package
            ->relations()
            ->map(static fn($relation) => $relation->name())
            ->filter(static fn($name) => !$packages->contains($name->toString()))
            ->flatMap(function($name): Set {
                /** @var Set<PackageModel> */
                return ($this->loadPackage)($name)->match(
                    static fn($package) => Set::of($package),
                    static fn() => Set::of(),
                );
            })
            ->reduce(
                $packages,
                fn(Map $packages, $relation) => $this->load(
                    ($packages)($relation->name()->toString(), $relation),
                    $relation,
                ),
            );
    }
}
