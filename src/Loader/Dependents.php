<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Package,
    Vendor as Model,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class Dependents
{
    private $load;

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
        return Set::of(Model\Name::class, $required, ...$vendors)
            ->reduce(
                Set::of(Model::class),
                function(SetInterface $vendors, Model\Name $vendor): SetInterface {
                    return $vendors->add(($this->load)($vendor));
                }
            )
            ->filter(static function(Model $vendor) use ($name): bool {
                return $vendor->dependsOn($name);
            })
            ->reduce(
                Set::of(Package::class),
                static function(SetInterface $packages, Model $vendor) use ($name): SetInterface {
                    return $packages->merge($vendor->dependingOn($name));
                }
            );
    }
}
