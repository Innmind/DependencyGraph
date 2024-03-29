<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Package as PackageModel,
    Vendor as VendorModel,
};
use Innmind\Immutable\{
    Set,
    Map,
    Maybe,
    Predicate\Instance,
};

final class VendorDependencies
{
    private Vendor $loadVendor;
    private Package $loadPackage;
    /** @var Map<string, \WeakReference<PackageModel>> */
    private Map $cache;

    public function __construct(
        Vendor $loadVendor,
        Package $loadPackage,
    ) {
        $this->loadVendor = $loadVendor;
        $this->loadPackage = $loadPackage;
        /** @var Map<string, \WeakReference<PackageModel>> */
        $this->cache = Map::of();
    }

    /**
     * @return Set<PackageModel>
     */
    public function __invoke(VendorModel\Name $name): Set
    {
        $packages = ($this->loadVendor)($name)
            ->packages()
            ->map($this->cache(...))
            ->flatMap($this->loadRelations(...));
        $concrete = $packages->map(static fn($package) => $package->name());

        // remove the relations with no stable releases
        return $packages->map(static fn($package) => $package->keep($concrete));
    }

    /**
     * @return Set<PackageModel>
     */
    private function loadRelations(PackageModel $dependency): Set
    {
        return $dependency
            ->relations()
            ->map(static fn($relation) => $relation->name())
            ->map($this->lookup(...))
            ->flatMap(
                static fn($package) => $package
                    ->toSequence()
                    ->toSet(),
            )
            ->add($dependency);
    }

    /**
     * @return Maybe<PackageModel>
     */
    private function lookup(PackageModel\Name $relation): Maybe
    {
        return Maybe::defer(fn() => $this->cache->get($relation->toString()))
            ->map(static fn($ref) => $ref->get())
            ->keep(Instance::of(PackageModel::class))
            ->otherwise(fn() => $this->fetch($relation));
    }

    /**
     * @return Maybe<PackageModel>
     */
    private function fetch(PackageModel\Name $relation): Maybe
    {
        // remove dead references
        $this->cache = $this->cache->filter(
            static fn($_, $ref) => \is_object($ref->get()),
        );

        return ($this->loadPackage)($relation)
            ->map($this->cache(...))
            ->map(static fn($package) => match ($package->abandoned()) {
                true => $package->keep(Set::of()), // remove dependencies to reduce clutter
                false => $package,
            });
    }

    private function cache(PackageModel $package): PackageModel
    {
        $this->cache = ($this->cache)(
            $package->name()->toString(),
            \WeakReference::create($package),
        );

        return $package;
    }
}
