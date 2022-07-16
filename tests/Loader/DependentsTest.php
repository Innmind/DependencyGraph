<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Dependents,
    Loader\Vendor,
    Loader\Package,
    Package as PackageModel,
    Vendor as VendorModel,
};
use Innmind\HttpTransport\Curl;
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class DependentsTest extends TestCase
{
    public function testInvokation()
    {
        $http = Curl::of(new Clock);

        $load = new Dependents(
            new Vendor(
                $http,
                new Package($http),
            ),
        );

        $packages = $load(
            PackageModel\Name::of('innmind/immutable'),
            new VendorModel\Name('innmind'),
        );

        $this->assertInstanceOf(Set::class, $packages);
        $this->assertCount(75, $packages);
    }
}
