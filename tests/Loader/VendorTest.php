<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Vendor,
    Loader\Package,
    Vendor as Model,
};
use Innmind\HttpTransport\Curl;
use Innmind\TimeContinuum\Earth\Clock;
use PHPUnit\Framework\TestCase;

class VendorTest extends TestCase
{
    public function testInvokation()
    {
        $http = Curl::of(new Clock);
        $load = new Vendor($http, new Package($http));

        $vendor = $load(Model\Name::of('innmind'));

        $this->assertInstanceOf(Model::class, $vendor);
        $this->assertSame('innmind', $vendor->name()->toString());
        $this->assertCount(82, $vendor->packages()->toList());
    }
}
