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
        $name = new Name('vendor');

        $this->assertSame('vendor', (string) $name);
    }

    public function testEquals()
    {
        $this->assertTrue((new Name('foo'))->equals(new Name('foo')));
        $this->assertFalse((new Name('foo'))->equals(new Name('bar')));
    }

    public function testThrowWhenEmptyVendor()
    {
        $this->expectException(DomainException::class);

        new Name('');
    }
}
