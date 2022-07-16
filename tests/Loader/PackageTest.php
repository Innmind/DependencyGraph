<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Package,
    Package as Model,
};
use Innmind\HttpTransport\Curl;
use Innmind\TimeContinuum\Earth\Clock;
use PHPUnit\Framework\TestCase;

class PackageTest extends TestCase
{
    public function testInvokation()
    {
        $load = new Package(Curl::of(new Clock));

        $package = $load(Model\Name::of('innmind/url'));

        $this->assertInstanceOf(Model::class, $package);
        $this->assertSame('innmind/url', $package->name()->toString());
        $this->assertSame('4.1.0', $package->version()->toString());
        $this->assertSame(
            'https://packagist.org/packages/innmind/url',
            $package->packagist()->toString(),
        );
        $this->assertCount(3, $package->relations());
    }

    public function testMostRecentVersionIsLoaded()
    {
        $load = new Package(Curl::of(new Clock));

        $package = $load(Model\Name::of('guzzlehttp/guzzle'));

        $this->assertSame('7.4.5', $package->version()->toString());
    }
}
