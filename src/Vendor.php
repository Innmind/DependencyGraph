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

final class Vendor
{
    private Vendor\Name $name;
    /** @var Set<Package> */
    private Set $packages;
    private Url $packagist;

    /**
     * @param Set<Package> $packages
     */
    public function __construct(Set $packages)
    {
        $first = $packages->find(static fn() => true)->match(
            static fn($first) => $first,
            static fn() => throw new \LogicException,
        );
        $this->name = $first->name()->vendor();
        $this->packages = $packages;
        $this->packagist = Url::of("https://packagist.org/packages/{$this->name->toString()}/");

        $_ = $this->packages->foreach(function(Package $package): void {
            if (!$package->name()->vendor()->equals($this->name)) {
                throw new LogicException;
            }
        });
    }

    /**
     * @param Set<Package> $packages
     *
     * @return Set<self>
     */
    public static function group(Set $packages): Set
    {
        $vendors = $packages
            ->groupBy(static function(Package $package): string {
                return $package->name()->vendor()->toString();
            })
            ->values()
            ->map(static fn($packages) => new self($packages));

        return Set::of(...$vendors->toList());
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
