<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\DependencyGraph\Exception\LogicException;
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class Vendor implements \Iterator
{
    private $name;
    private $packages;

    public function __construct(Package $first, Package ...$others)
    {
        $this->name = $first->name()->vendor();
        $this->packages = Set::of(Package::class, $first, ...$others)->foreach(function(Package $package): void {
            if ($package->name()->vendor() !== $this->name) {
                throw new LogicException;
            }
        });
    }

    /**
     * @return SetInterface<self>
     */
    public static function group(Package ...$packages): SetInterface
    {
        return Set::of(Package::class, ...$packages)
            ->groupBy(static function(Package $package): string {
                return $package->name()->vendor();
            })
            ->values()
            ->reduce(
                Set::of(self::class),
                static function(SetInterface $vendors, SetInterface $packages): SetInterface {
                    return $vendors->add(new Vendor(...$packages));
                }
            );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function current()
    {
        return $this->packages->current();
    }

    public function key()
    {
        return $this->packages->key();
    }

    public function next()
    {
        return $this->packages->next();
    }

    public function rewind()
    {
        return $this->packages->rewind();
    }

    public function valid()
    {
        return $this->packages->valid();
    }
}
