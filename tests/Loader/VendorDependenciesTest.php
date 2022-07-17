<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\VendorDependencies,
    Loader\Vendor,
    Loader\Package,
    Vendor\Name,
    Package as Model,
};
use Innmind\HttpTransport\Curl;
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class VendorDependenciesTest extends TestCase
{
    public function testInvokation()
    {
        $http = Curl::of(new Clock);
        $package = new Package($http);
        $load = new VendorDependencies(
            new Vendor($http, $package),
            $package,
        );

        $vendor = $load(Name::of('innmind'));

        $this->assertInstanceOf(Set::class, $vendor);
        $this->assertCount(184, $vendor);
    }
}
