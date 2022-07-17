<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Vendor;

use Innmind\DependencyGraph\{
    Vendor\Name,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class NameTest extends TestCase
{
    public function testInterface()
    {
        $name = Name::of('vendor');

        $this->assertSame('vendor', $name->toString());
    }

    public function testEquals()
    {
        $this->assertTrue(Name::of('foo')->equals(Name::of('foo')));
        $this->assertFalse(Name::of('foo')->equals(Name::of('bar')));
    }

    public function testThrowWhenEmptyVendor()
    {
        $this->expectException(DomainException::class);

        Name::of('');
    }
}
