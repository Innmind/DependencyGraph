<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Vendor,
    Package,
    Package\Relation,
    Package\Name,
    Package\Version,
    Package\Constraint,
    Exception\LogicException,
};
use Innmind\Url\Url;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class VendorTest extends TestCase
{
    public function testInterface()
    {
        $vendor = new Vendor(
            Set::of(
                $bar = new Package(
                    new Name(new Vendor\Name('foo'), 'bar'),
                    new Version('1.0.0'),
                    Url::of('http://example.com'),
                    Set::of(),
                ),
                $baz = new Package(
                    new Name(new Vendor\Name('foo'), 'baz'),
                    new Version('1.0.0'),
                    Url::of('http://example.com'),
                    Set::of(),
                ),
            ),
        );

        $this->assertInstanceOf(Vendor\Name::class, $vendor->name());
        $this->assertSame('foo', $vendor->name()->toString());
        $this->assertInstanceOf(Url::class, $vendor->packagist());
        $this->assertSame('https://packagist.org/packages/foo/', $vendor->packagist()->toString());
        $this->assertSame([$bar, $baz], $vendor->packages()->toList());
    }

    public function testThrowWhenPackagesDoNotBelongToTheSameVendor()
    {
        $this->expectException(LogicException::class);

        new Vendor(
            Set::of(
                new Package(
                    new Name(new Vendor\Name('foo'), 'bar'),
                    new Version('1.0.0'),
                    Url::of('http://example.com'),
                    Set::of(),
                ),
                new Package(
                    new Name(new Vendor\Name('bar'), 'baz'),
                    new Version('1.0.0'),
                    Url::of('http://example.com'),
                    Set::of(),
                ),
            ),
        );
    }

    public function testGroup()
    {
        $vendors = Vendor::group(
            Set::of(
                $foo = new Package(
                    new Name(new Vendor\Name('foo'), 'bar'),
                    new Version('1.0.0'),
                    Url::of('http://example.com'),
                    Set::of(),
                ),
                $bar = new Package(
                    new Name(new Vendor\Name('bar'), 'baz'),
                    new Version('1.0.0'),
                    Url::of('http://example.com'),
                    Set::of(),
                ),
            ),
        );

        $this->assertInstanceOf(Set::class, $vendors);
        $this->assertCount(2, $vendors);
        $vendors = $vendors->toList();
        $this->assertSame([$foo], \current($vendors)->packages()->toList());
        \next($vendors);
        $this->assertSame([$bar], \current($vendors)->packages()->toList());
    }

    public function testDependsOn()
    {
        $vendor = new Vendor(
            Set::of(
                new Package(
                    Name::of('foo/bar'),
                    new Version('1.0.0'),
                    Url::of('http://example.com'),
                    Set::of(),
                ),
                new Package(
                    Name::of('foo/baz'),
                    new Version('1.0.0'),
                    Url::of('http://example.com'),
                    Set::of(
                        new Relation(Name::of('bar/baz'), new Constraint('~1.0')),
                    ),
                ),
            ),
        );

        $this->assertTrue($vendor->dependsOn(Name::of('bar/baz')));
        $this->assertFalse($vendor->dependsOn(Name::of('foo/baz')));
    }
}
