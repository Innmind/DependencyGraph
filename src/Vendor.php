<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\Url\Url;
use Innmind\Immutable\Set;

/**
 * @psalm-immutable
 */
final class Vendor
{
    private Vendor\Name $name;
    /** @var Set<Package> */
    private Set $packages;
    private Url $packagist;

    /**
     * @param Set<Package> $packages
     */
    public function __construct(Vendor\Name $name, Set $packages)
    {
        $this->name = $name;
        $this->packages = $packages->filter(
            static fn($package) => $package->name()->vendor()->equals($name),
        );
        $this->packagist = Url::of("https://packagist.org/packages/{$this->name->toString()}/");
    }

    /**
     * @psalm-pure
     *
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
            ->map(static fn($name, $packages) => new self(
                Vendor\Name::of($name),
                $packages,
            ))
            ->values();

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
