<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Package,
    Package\Name,
    Package\Version,
    Package\Relation,
    Package\Constraint,
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
            $version = new Version('1.0.0'),
            $packagist = $this->createMock(UrlInterface::class),
            $relation = new Relation(
                new Name(new Vendor\Name('bar'), 'baz'),
                new Constraint('~1.0')
            )
        );

        $this->assertSame($name, $package->name());
        $this->assertSame($version, $package->version());
        $this->assertSame($packagist, $package->packagist());
        $this->assertInstanceOf(SetInterface::class, $package->relations());
        $this->assertSame(Relation::class, (string) $package->relations()->type());
        $this->assertSame([$relation], $package->relations()->toPrimitive());
    }

    public function testDependsOn()
    {
        $package = new Package(
            Name::of('foo/bar'),
            new Version('1.0.0'),
            $this->createMock(UrlInterface::class),
            new Relation(Name::of('bar/baz'), new Constraint('~1.0'))
        );

        $this->assertTrue($package->dependsOn(Name::of('bar/baz')));
        $this->assertFalse($package->dependsOn(Name::of('foo/baz')));
    }

    public function testKeep()
    {
        $package = new Package(
            Name::of('foo/bar'),
            new Version('1.0.0'),
            $this->createMock(UrlInterface::class),
            $bar = new Relation(Name::of('bar/baz'), new Constraint('~1.0')),
            new Relation(Name::of('baz/foo'), new Constraint('~1.0')),
            $foo = new Relation(Name::of('foo/bar'), new Constraint('~1.0'))
        );

        $package2 = $package->keep(Name::of('foo/bar'), Name::of('bar/baz'));

        $this->assertInstanceOf(Package::class, $package2);
        $this->assertNotSame($package, $package2);
        $this->assertCount(3, $package->relations());
        $this->assertCount(2, $package2->relations());
        $this->assertSame([$bar, $foo], $package2->relations()->toPrimitive());
    }

    public function testRemoveRelations()
    {
        $package = new Package(
            Name::of('foo/bar'),
            new Version('1.0.0'),
            $this->createMock(UrlInterface::class),
            $bar = new Relation(Name::of('bar/baz'), new Constraint('~1.0')),
            new Relation(Name::of('baz/foo'), new Constraint('~1.0')),
            $foo = new Relation(Name::of('foo/bar'), new Constraint('~1.0'))
        );

        $package2 = $package->removeRelations();

        $this->assertInstanceOf(Package::class, $package2);
        $this->assertNotSame($package, $package2);
        $this->assertCount(3, $package->relations());
        $this->assertCount(0, $package2->relations());
    }
}
