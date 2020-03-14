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

final class Vendor implements \Iterator
{
    private Vendor\Name $name;
    private array $packages;
    private Url $packagist;

    public function __construct(Package $first, Package ...$others)
    {
        $this->name = $first->name()->vendor();
        $packages = Set::of(Package::class, $first, ...$others);
        $this->packagist = Url::of("https://packagist.org/packages/{$this->name}/");

        $packages->foreach(function(Package $package): void {
            if (!$package->name()->vendor()->equals($this->name)) {
                throw new LogicException;
            }
        });

        $this->packages = unwrap($packages);
    }

    /**
     * @return Set<self>
     */
    public static function group(Package ...$packages): Set
    {
        return Set::of(Package::class, ...$packages)
            ->groupBy(static function(Package $package): string {
                return (string) $package->name()->vendor();
            })
            ->values()
            ->reduce(
                Set::of(self::class),
                static function(Set $vendors, Set $packages): Set {
                    return $vendors->add(new Vendor(...unwrap($packages)));
                }
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
        return Set::of(Package::class, ...$this->packages)->reduce(
            false,
            static function(bool $dependsOn, Package $package) use ($name): bool {
                return $dependsOn || $package->dependsOn($name);
            }
        );
    }

    public function current(): Package
    {
        return \current($this->packages);
    }

    public function key(): int
    {
        return \key($this->packages);
    }

    public function next(): void
    {
        \next($this->packages);
    }

    public function rewind(): void
    {
        \reset($this->packages);
    }

    public function valid(): bool
    {
        return \current($this->packages) instanceof Package;
    }
}
