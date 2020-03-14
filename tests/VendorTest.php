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
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class VendorTest extends TestCase
{
    public function testInterface()
    {
        $vendor = new Vendor(
            $bar = new Package(
                new Name(new Vendor\Name('foo'), 'bar'),
                new Version('1.0.0'),
                Url::of('http://example.com')
            ),
            $baz = new Package(
                new Name(new Vendor\Name('foo'), 'baz'),
                new Version('1.0.0'),
                Url::of('http://example.com')
            )
        );

        $this->assertInstanceOf(Vendor\Name::class, $vendor->name());
        $this->assertSame('foo', (string) $vendor->name());
        $this->assertInstanceOf(Url::class, $vendor->packagist());
        $this->assertSame('https://packagist.org/packages/foo/', $vendor->packagist()->toString());
        $this->assertInstanceOf(\Iterator::class, $vendor);
        $this->assertSame([$bar, $baz], iterator_to_array($vendor));
    }

    public function testThrowWhenPackagesDoNotBelongToTheSameVendor()
    {
        $this->expectException(LogicException::class);

        new Vendor(
            new Package(
                new Name(new Vendor\Name('foo'), 'bar'),
                new Version('1.0.0'),
                Url::of('http://example.com')
            ),
            new Package(
                new Name(new Vendor\Name('bar'), 'baz'),
                new Version('1.0.0'),
                Url::of('http://example.com')
            )
        );
    }

    public function testGroup()
    {
        $vendors = Vendor::group(
            $foo = new Package(
                new Name(new Vendor\Name('foo'), 'bar'),
                new Version('1.0.0'),
                Url::of('http://example.com')
            ),
            $bar = new Package(
                new Name(new Vendor\Name('bar'), 'baz'),
                new Version('1.0.0'),
                Url::of('http://example.com')
            )
        );

        $this->assertInstanceOf(Set::class, $vendors);
        $this->assertSame(Vendor::class, (string) $vendors->type());
        $this->assertCount(2, $vendors);
        $vendors = unwrap($vendors);
        $this->assertSame([$foo], iterator_to_array(\current($vendors)));
        \next($vendors);
        $this->assertSame([$bar], iterator_to_array(\current($vendors)));
    }

    public function testDependsOn()
    {
        $vendor = new Vendor(
            new Package(
                Name::of('foo/bar'),
                new Version('1.0.0'),
                Url::of('http://example.com')
            ),
            new Package(
                Name::of('foo/baz'),
                new Version('1.0.0'),
                Url::of('http://example.com'),
                new Relation(Name::of('bar/baz'), new Constraint('~1.0'))
            )
        );

        $this->assertTrue($vendor->dependsOn(Name::of('bar/baz')));
        $this->assertFalse($vendor->dependsOn(Name::of('foo/baz')));
    }
}
