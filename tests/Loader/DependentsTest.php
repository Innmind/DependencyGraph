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
        $http = Curl::of(new Clock)->maxConcurrency(20);

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
        $this->assertCount(78, $packages);
        $this->assertSame(
            [
                'innmind/acl',
                'innmind/amqp',
                'innmind/ark',
                'innmind/async-http-server',
                'innmind/black-box',
                'innmind/black-box-symfony',
                'innmind/cli',
                'innmind/colour',
                'innmind/crawler',
                'innmind/crawler-app',
                'innmind/cron',
                'innmind/debug',
                'innmind/dependency-graph',
                'innmind/doctrine',
                'innmind/encoding',
                'innmind/file-watch',
                'innmind/filesystem',
                'innmind/framework',
                'innmind/genome',
                'innmind/git',
                'innmind/git-release',
                'innmind/graphviz',
                'innmind/hash',
                'innmind/homeostasis',
                'innmind/html',
                'innmind/http',
                'innmind/http-authentication',
                'innmind/http-parser',
                'innmind/http-server',
                'innmind/http-session',
                'innmind/http-transport',
                'innmind/immutable',
                'innmind/infrastructure',
                'innmind/infrastructure-amqp',
                'innmind/infrastructure-neo4j',
                'innmind/infrastructure-nginx',
                'innmind/installation-monitor',
                'innmind/io',
                'innmind/ip',
                'innmind/ipc',
                'innmind/json',
                'innmind/kalmiya',
                'innmind/lab-station',
                'innmind/library',
                'innmind/log-reader',
                'innmind/logger',
                'innmind/mantle',
                'innmind/math',
                'innmind/media-type',
                'innmind/object-graph',
                'innmind/operating-system',
                'innmind/profiler',
                'innmind/rabbitmq-management',
                'innmind/reflection',
                'innmind/robots-txt',
                'innmind/router',
                'innmind/s3',
                'innmind/scaleway-sdk',
                'innmind/server-control',
                'innmind/server-status',
                'innmind/signals',
                'innmind/silent-cartographer',
                'innmind/socket',
                'innmind/ssh-key-provider',
                'innmind/stack-trace',
                'innmind/stream',
                'innmind/templating',
                'innmind/time-continuum',
                'innmind/time-warp',
                'innmind/tower',
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
        $http = Curl::of(new Clock)->maxConcurrency(20);

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
        $this->assertCount(1, $packages);
    }
}
