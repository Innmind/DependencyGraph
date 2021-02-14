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
        $this->assertSame('innmind/url', $package->name()->toString());
        $this->assertSame('3.6.0', $package->version()->toString());
        $this->assertSame(
            'https://packagist.org/packages/innmind/url',
            $package->packagist()->toString(),
        );
        $this->assertCount(2, $package->relations());
    }

    public function testMostRecentVersionIsLoaded()
    {
        $load = new Package(http()['default']());

        $package = $load(Model\Name::of('guzzlehttp/guzzle'));

        $this->assertSame('7.0.1', $package->version()->toString());
    }
}
