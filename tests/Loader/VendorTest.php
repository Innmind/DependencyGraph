<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Vendor,
    Vendor as Model,
};
use function Innmind\HttpTransport\bootstrap as http;
use PHPUnit\Framework\TestCase;

class VendorTest extends TestCase
{
    public function testInvokation()
    {
        $load = new Vendor(http()['default']());

        $vendor = $load(new Model\Name('innmind'));

        $this->assertInstanceOf(Model::class, $vendor);
        $this->assertSame('innmind', (string) $vendor->name());
        $this->assertCount(59, $vendor);
    }
}
