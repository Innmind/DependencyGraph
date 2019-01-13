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
}
