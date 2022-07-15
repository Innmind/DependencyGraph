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
use function Innmind\HttpTransport\bootstrap as http;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class DependentsTest extends TestCase
{
    public function testInvokation()
    {
        $http = http()['default']();

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
        $this->assertSame(PackageModel::class, (string) $packages->type());
        $this->assertCount(75, $packages);
    }
}
