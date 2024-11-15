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

        $package = $load(Model\Name::of('innmind/url'))->match(
            static fn($package) => $package,
            static fn() => null,
        );

        $this->assertInstanceOf(Model::class, $package);
        $this->assertSame('innmind/url', $package->name()->toString());
        $this->assertSame('4.3.2', $package->version()->toString());
        $this->assertSame(
            'https://packagist.org/packages/innmind/url',
            $package->packagist()->toString(),
        );
        $this->assertCount(3, $package->relations());
    }

    public function testMostRecentVersionIsLoaded()
    {
        $load = new Package(Curl::of(new Clock));

        $package = $load(Model\Name::of('guzzlehttp/guzzle'))->match(
            static fn($package) => $package,
            static fn() => null,
        );

        $this->assertInstanceOf(Model::class, $package);
        $this->assertSame('7.9.2', $package->version()->toString());
    }

    public function testReturnNothingWhenThePackageDoesNotExist()
    {
        $load = new Package(Curl::of(new Clock));

        $package = $load(Model\Name::of('psr/http-message-implementation'))->match(
            static fn($package) => $package,
            static fn() => null,
        );

        $this->assertNull($package);
    }
}
