<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Vendor,
    Package,
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
                new Name('foo', 'bar'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class)
            ),
            $baz = new Package(
                new Name('foo', 'baz'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertSame('foo', $vendor->name());
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
                new Name('foo', 'bar'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class)
            ),
            new Package(
                new Name('bar', 'baz'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class)
            )
        );
    }

    public function testGroup()
    {
        $vendors = Vendor::group(
            $foo = new Package(
                new Name('foo', 'bar'),
                $this->createMock(UrlInterface::class),
                $this->createMock(UrlInterface::class)
            ),
            $bar = new Package(
                new Name('bar', 'baz'),
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
}
