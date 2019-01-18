<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\{
    Package\Name,
    Vendor,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class NameTest extends TestCase
{
    public function testInterface()
    {
        $name = new Name(new Vendor\Name('vendor'), 'package');

        $this->assertInstanceOf(Vendor\Name::class, $name->vendor());
        $this->assertSame('vendor', (string) $name->vendor());
        $this->assertSame('package', $name->package());
        $this->assertSame('vendor/package', (string) $name);
    }

    public function testOf()
    {
        $name = Name::of('vendor/package');

        $this->assertInstanceOf(Name::class, $name);
        $this->assertSame('vendor/package', (string) $name);
    }

    public function testEquals()
    {
        $this->assertTrue(Name::of('foo/bar')->equals(Name::of('foo/bar')));
        $this->assertFalse(Name::of('foo/bar')->equals(Name::of('foo/baz')));
    }

    public function testThrowWhenEmptyPackage()
    {
        $this->expectException(DomainException::class);

        new Name(new Vendor\Name('vendor'), '');
    }
}
