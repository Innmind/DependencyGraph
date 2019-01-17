<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Vendor,
    Package,
    Package\Relation,
    Package\Name,
    Exception\LogicException,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\SetInterface;
use PHPUnit\Framework\TestCase;

class VendorTest extends TestCase
{
    public function testInterface()
    {
        $vendor = new Vendor(
            $bar = new Package(
                new Name(new Vendor\Name('foo'), 'bar'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class)
            ),
            $baz = new Package(
                new Name(new Vendor\Name('foo'), 'baz'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertInstanceOf(Vendor\Name::class, $vendor->name());
        $this->assertSame('foo', (string) $vendor->name());
        $this->assertInstanceOf(UrlInterface::class, $vendor->packagist());
        $this->assertSame('https://packagist.org/packages/foo/', (string) $vendor->packagist());
        $this->assertInstanceOf(\Iterator::class, $vendor);
        $this->assertSame([$bar, $baz], iterator_to_array($vendor));
    }

    public function testThrowWhenPackagesDoNotBelongToTheSameVendor()
    {
        $this->expectException(LogicException::class);

        new Vendor(
            new Package(
                new Name(new Vendor\Name('foo'), 'bar'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class)
            ),
            new Package(
                new Name(new Vendor\Name('bar'), 'baz'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class)
            )
        );
    }

    public function testGroup()
    {
        $vendors = Vendor::group(
            $foo = new Package(
                new Name(new Vendor\Name('foo'), 'bar'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class)
            ),
            $bar = new Package(
                new Name(new Vendor\Name('bar'), 'baz'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertInstanceOf(SetInterface::class, $vendors);
        $this->assertSame(Vendor::class, (string) $vendors->type());
        $this->assertCount(2, $vendors);
        $this->assertSame([$foo], iterator_to_array($vendors->current()));
        $vendors->next();
        $this->assertSame([$bar], iterator_to_array($vendors->current()));
    }

    public function testDependsOn()
    {
        $vendor = new Vendor(
            new Package(
                Name::of('foo/bar'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class)
            ),
            new Package(
                Name::of('foo/baz'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class),
                new Relation(Name::of('bar/baz'))
            )
        );

        $this->assertTrue($vendor->dependsOn(Name::of('bar/baz')));
        $this->assertFalse($vendor->dependsOn(Name::of('foo/baz')));
    }

    public function testDependingOn()
    {
        $vendor = new Vendor(
            new Package(
                Name::of('foo/bar'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class)
            ),
            $expected = new Package(
                Name::of('foo/baz'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class),
                new Relation(Name::of('bar/baz'))
            )
        );

        $packages = $vendor->dependingOn(Name::of('bar/baz'));

        $this->assertInstanceOf(SetInterface::class, $packages);
        $this->assertSame(Package::class, (string) $packages->type());
        $this->assertSame([$expected], $packages->toPrimitive());
    }

    public function testReliedVendors()
    {
        $vendor = new Vendor(
            new Package(
                Name::of('foo/bar'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class),
                new Relation(Name::of('bar/foo'))
            ),
            new Package(
                Name::of('foo/baz'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class),
                new Relation(Name::of('foo/baz'))
            ),
            new Package(
                Name::of('foo/foo'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class),
                new Relation(Name::of('bar/baz'))
            )
        );

        $vendors = $vendor->reliedVendors();

        $this->assertInstanceOf(SetInterface::class, $vendors);
        $this->assertSame(Vendor\Name::class, (string) $vendors->type());
        $this->assertCount(2, $vendors);
        $this->assertSame('bar', (string) $vendors->current());
        $vendors->next();
        $this->assertSame('foo', (string) $vendors->current());
    }
}
