<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Package,
    Package\Name,
    Package\Version,
    Package\Relation,
    Package\Constraint,
};
use Innmind\Url\Url;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class PackageTest extends TestCase
{
    public function testInterface()
    {
        $package = new Package(
            $name = Name::of('foo/bar'),
            $version = Version::of('1.0.0'),
            $packagist = Url::of('http://example.com'),
            $relations = Set::of(new Relation(
                Name::of('bar/baz'),
                new Constraint('~1.0'),
            )),
        );

        $this->assertSame($name, $package->name());
        $this->assertSame($version, $package->version());
        $this->assertSame($packagist, $package->packagist());
        $this->assertInstanceOf(Set::class, $package->relations());
        $this->assertSame($relations, $package->relations());
    }

    public function testDependsOn()
    {
        $package = new Package(
            Name::of('foo/bar'),
            Version::of('1.0.0'),
            Url::of('http://example.com'),
            Set::of(
                new Relation(Name::of('bar/baz'), new Constraint('~1.0')),
            ),
        );

        $this->assertTrue($package->dependsOn(Name::of('bar/baz')));
        $this->assertFalse($package->dependsOn(Name::of('foo/baz')));
    }

    public function testKeep()
    {
        $package = new Package(
            Name::of('foo/bar'),
            Version::of('1.0.0'),
            Url::of('http://example.com'),
            Set::of(
                $bar = new Relation(Name::of('bar/baz'), new Constraint('~1.0')),
                new Relation(Name::of('baz/foo'), new Constraint('~1.0')),
                $foo = new Relation(Name::of('foo/bar'), new Constraint('~1.0')),
            ),
        );

        $package2 = $package->keep(Set::of(Name::of('foo/bar'), Name::of('bar/baz')));

        $this->assertInstanceOf(Package::class, $package2);
        $this->assertNotSame($package, $package2);
        $this->assertCount(3, $package->relations());
        $this->assertCount(2, $package2->relations());
        $this->assertSame([$bar, $foo], $package2->relations()->toList());
    }

    public function testRemoveRelations()
    {
        $package = new Package(
            Name::of('foo/bar'),
            Version::of('1.0.0'),
            Url::of('http://example.com'),
            Set::of(
                $bar = new Relation(Name::of('bar/baz'), new Constraint('~1.0')),
                new Relation(Name::of('baz/foo'), new Constraint('~1.0')),
                $foo = new Relation(Name::of('foo/bar'), new Constraint('~1.0')),
            ),
        );

        $package2 = $package->removeRelations();

        $this->assertInstanceOf(Package::class, $package2);
        $this->assertNotSame($package, $package2);
        $this->assertCount(3, $package->relations());
        $this->assertCount(0, $package2->relations());
    }
}
