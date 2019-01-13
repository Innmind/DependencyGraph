<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\{
    Package\Name,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class NameTest extends TestCase
{
    public function testInterface()
    {
        $name = new Name('vendor', 'package');

        $this->assertSame('vendor', $name->vendor());
        $this->assertSame('package', $name->package());
        $this->assertSame('vendor/package', (string) $name);
    }

    public function testOf()
    {
        $name = Name::of('vendor/package');

        $this->assertInstanceOf(Name::class, $name);
        $this->assertSame('vendor/package', (string) $name);
    }

    public function testThrowWhenEmptyVendor()
    {
        $this->expectException(DomainException::class);

        new Name('', 'package');
    }

    public function testThrowWhenEmptyPackage()
    {
        $this->expectException(DomainException::class);

        new Name('vendor', '');
    }
}
