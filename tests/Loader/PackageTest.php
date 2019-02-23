<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Package,
    Package as Model,
};
use function Innmind\HttpTransport\bootstrap as http;
use PHPUnit\Framework\TestCase;

class PackageTest extends TestCase
{
    public function testInvokation()
    {
        $load = new Package(http()['default']());

        $package = $load(Model\Name::of('innmind/url'));

        $this->assertInstanceOf(Model::class, $package);
        $this->assertSame('innmind/url', (string) $package->name());
        $this->assertSame('dev-master', (string) $package->version());
        $this->assertSame(
            'https://packagist.org/packages/innmind/url',
            (string) $package->packagist())
        ;
        $this->assertCount(2, $package->relations());
    }
}
