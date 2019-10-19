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
use function Innmind\HttpTransport\bootstrap as http;
use Innmind\Immutable\SetInterface;
use PHPUnit\Framework\TestCase;

class VendorDependenciesTest extends TestCase
{
    public function testInvokation()
    {
        $http = http()['default']();
        $package = new Package($http);
        $load = new VendorDependencies(
            new Vendor($http, $package),
            $package
        );

        $vendor = $load(new Name('innmind'));

        $this->assertInstanceOf(SetInterface::class, $vendor);
        $this->assertSame(Model::class, (string) $vendor->type());
        $this->assertCount(102, $vendor);
    }
}
