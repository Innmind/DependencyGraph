<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Package\Relation,
    Exception\LogicException,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Set,
    Map,
};
use function Innmind\Immutable\unwrap;

final class Vendor
{
    private Vendor\Name $name;
    /** @var Set<Package> */
    private Set $packages;
    private Url $packagist;

    public function __construct(Package $first, Package ...$others)
    {
        $this->name = $first->name()->vendor();
        $this->packages = Set::of(Package::class, $first, ...$others);
        $this->packagist = Url::of("https://packagist.org/packages/{$this->name->toString()}/");

        $this->packages->foreach(function(Package $package): void {
            if (!$package->name()->vendor()->equals($this->name)) {
                throw new LogicException;
            }
        });
    }

    /**
     * @return Set<self>
     */
    public static function group(Package ...$packages): Set
    {
        /** @var Set<self> */
        return Set::of(Package::class, ...$packages)
            ->groupBy(static function(Package $package): string {
                return $package->name()->vendor()->toString();
            })
            ->values()
            ->toSetOf(
                self::class,
                static fn(Set $packages): \Generator => yield new self(
                    ...unwrap($packages),
                ),
            );
    }

    public function name(): Vendor\Name
    {
        return $this->name;
    }

    public function packagist(): Url
    {
        return $this->packagist;
    }

    public function dependsOn(Package\Name $name): bool
    {
        return $this->packages->reduce(
            false,
            static function(bool $dependsOn, Package $package) use ($name): bool {
                return $dependsOn || $package->dependsOn($name);
            },
        );
    }

    /**
     * @return Set<Package>
     */
    public function packages(): Set
    {
        return $this->packages;
    }
}
