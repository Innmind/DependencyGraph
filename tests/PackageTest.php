<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Package,
    Package\Name,
    Package\Relation,
    Vendor,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\SetInterface;
use PHPUnit\Framework\TestCase;

class PackageTest extends TestCase
{
    public function testInterface()
    {
        $package = new Package(
            $name = new Name(new Vendor\Name('foo'), 'bar'),
            $packagist = $this->createMock(UrlInterface::class),
            $repository = $this->createMock(UrlInterface::class),
            $relation = new Relation(new Name(new Vendor\Name('bar'), 'baz'))
        );

        $this->assertSame($name, $package->name());
        $this->assertSame($packagist, $package->packagist());
        $this->assertSame($repository, $package->repository());
        $this->assertInstanceOf(SetInterface::class, $package->relations());
        $this->assertSame(Relation::class, (string) $package->relations()->type());
        $this->assertSame([$relation], $package->relations()->toPrimitive());
    }

    public function testDependsOn()
    {
        $package = new Package(
            Name::of('foo/bar'),
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            new Relation(Name::of('bar/baz'))
        );

        $this->assertTrue($package->dependsOn(Name::of('bar/baz')));
        $this->assertFalse($package->dependsOn(Name::of('foo/baz')));
    }

    public function testKeep()
    {
        $package = new Package(
            Name::of('foo/bar'),
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            $bar = new Relation(Name::of('bar/baz')),
            new Relation(Name::of('baz/foo')),
            $foo = new Relation(Name::of('foo/bar'))
        );

        $package2 = $package->keep(Name::of('foo/bar'), Name::of('bar/baz'));

        $this->assertInstanceOf(Package::class, $package2);
        $this->assertNotSame($package, $package2);
        $this->assertCount(3, $package->relations());
        $this->assertCount(2, $package2->relations());
        $this->assertSame([$bar, $foo], $package2->relations()->toPrimitive());
    }

    public function testRemove()
    {
        $package = new Package(
            Name::of('foo/bar'),
            $this->createMock(UrlInterface::class),
            $this->createMock(UrlInterface::class),
            $bar = new Relation(Name::of('bar/baz')),
            new Relation(Name::of('baz/foo')),
            $foo = new Relation(Name::of('foo/bar'))
        );

        $package2 = $package->remove(Name::of('baz/foo'));

        $this->assertInstanceOf(Package::class, $package2);
        $this->assertNotSame($package, $package2);
        $this->assertCount(3, $package->relations());
        $this->assertCount(2, $package2->relations());
        $this->assertSame([$bar, $foo], $package2->relations()->toPrimitive());
    }
}
