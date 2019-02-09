<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Vendor,
    Loader\Package,
    Vendor as Model,
};
use function Innmind\HttpTransport\bootstrap as http;
use PHPUnit\Framework\TestCase;

class VendorTest extends TestCase
{
    public function testInvokation()
    {
        $http = http()['default']();
        $load = new Vendor($http, new Package($http));

        $vendor = $load(new Model\Name('innmind'));

        $this->assertInstanceOf(Model::class, $vendor);
        $this->assertSame('innmind', (string) $vendor->name());
        $this->assertCount(63, $vendor);
    }
}
