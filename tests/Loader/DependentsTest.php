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
use Innmind\HttpTransport\Transport;
use Innmind\Time\Clock;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class DependentsTest extends TestCase
{
    public function testInvokation()
    {
        $http = Transport::curl(Clock::live())->map(
            static fn($config) => $config->limitConcurrencyTo(20),
        );

        $load = new Dependents(
            new Vendor(
                $http,
                new Package($http),
            ),
        );

        $packages = $load(
            PackageModel\Name::of('innmind/immutable'),
            Set::of(VendorModel\Name::of('innmind')),
        );

        $this->assertInstanceOf(Set::class, $packages);
        $this->assertSame(61, $packages->size());
        $this->assertSame(
            [
                'innmind/acl',
                'innmind/amqp',
                'innmind/async',
                'innmind/async-http-server',
                'innmind/black-box-symfony',
                'innmind/cli',
                'innmind/colour',
                'innmind/crawler',
                'innmind/cron',
                'innmind/debug',
                'innmind/dependency-graph',
                'innmind/di',
                'innmind/encoding',
                'innmind/file-watch',
                'innmind/filesystem',
                'innmind/foundation',
                'innmind/framework',
                'innmind/git',
                'innmind/git-release',
                'innmind/graphviz',
                'innmind/hash',
                'innmind/html',
                'innmind/http',
                'innmind/http-authentication',
                'innmind/http-parser',
                'innmind/http-server',
                'innmind/http-session',
                'innmind/http-transport',
                'innmind/immutable',
                'innmind/io',
                'innmind/ip',
                'innmind/ipc',
                'innmind/json',
                'innmind/kalmiya',
                'innmind/lab-station',
                'innmind/log-reader',
                'innmind/logger',
                'innmind/math',
                'innmind/media-type',
                'innmind/mutable',
                'innmind/object-graph',
                'innmind/operating-system',
                'innmind/profiler',
                'innmind/rabbitmq-management',
                'innmind/reflection',
                'innmind/robots-txt',
                'innmind/router',
                'innmind/s3',
                'innmind/server-control',
                'innmind/server-status',
                'innmind/signals',
                'innmind/stack-trace',
                'innmind/time',
                'innmind/ui',
                'innmind/url',
                'innmind/url-resolver',
                'innmind/url-template',
                'innmind/validation',
                'innmind/virtual-machine',
                'innmind/warden',
                'innmind/xml',
            ],
            $packages
                ->map(static fn($package) => $package->name()->toString())
                ->sort(static fn($a, $b) => $a <=> $b)
                ->toList(),
        );
    }
    public function testCircularDependencyRegression()
    {
        $http = Transport::curl(Clock::live())->map(
            static fn($config) => $config->limitConcurrencyTo(20),
        );

        $load = new Dependents(
            new Vendor(
                $http,
                new Package($http),
            ),
        );

        $packages = $load(
            PackageModel\Name::of('league/climate'),
            Set::of(VendorModel\Name::of('league')),
        );

        $this->assertInstanceOf(Set::class, $packages);
        $this->assertSame(1, $packages->size());
    }
}
