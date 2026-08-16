<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Package,
    Package as Model,
};
use Innmind\HttpTransport\Transport;
use Innmind\Time\Clock;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class PackageTest extends TestCase
{
    public function testInvokation()
    {
        $load = new Package(Transport::curl(Clock::live()));

        $package = $load(Model\Name::of('innmind/url'))->match(
            static fn($package) => $package,
            static fn() => null,
        );

        $this->assertInstanceOf(Model::class, $package);
        $this->assertSame('innmind/url', $package->name()->toString());
        $this->assertSame('5.4.0', $package->version()->toString());
        $this->assertSame(
            'https://packagist.org/packages/innmind/url',
            $package->packagist()->toString(),
        );
        $this->assertSame(1, $package->relations()->size());
    }

    public function testMostRecentVersionIsLoaded()
    {
        $load = new Package(Transport::curl(Clock::live()));

        $package = $load(Model\Name::of('guzzlehttp/guzzle'))->match(
            static fn($package) => $package,
            static fn() => null,
        );

        $this->assertInstanceOf(Model::class, $package);
        $this->assertSame('8.0.2', $package->version()->toString());
    }

    public function testReturnNothingWhenThePackageDoesNotExist()
    {
        $load = new Package(Transport::curl(Clock::live()));

        $package = $load(Model\Name::of('psr/http-message-implementation'))->match(
            static fn($package) => $package,
            static fn() => null,
        );

        $this->assertNull($package);
    }
}
