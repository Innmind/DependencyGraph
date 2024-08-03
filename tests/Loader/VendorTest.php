<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Vendor,
    Loader\Package,
    Vendor as Model,
};
use Innmind\HttpTransport\Curl;
use Innmind\TimeContinuum\Earth\Clock;
use PHPUnit\Framework\TestCase;

class VendorTest extends TestCase
{
    public function testInvokation()
    {
        $http = Curl::of(new Clock)->maxConcurrency(20);
        $load = new Vendor($http, new Package($http));

        $vendor = $load(Model\Name::of('innmind'));

        $this->assertInstanceOf(Model::class, $vendor);
        $this->assertSame('innmind', $vendor->name()->toString());
        $this->assertSame(
            [
                'innmind/acl',
                'innmind/amqp',
                'innmind/ark',
                'innmind/async-http-server',
                'innmind/black-box',
                'innmind/black-box-symfony',
                'innmind/cli',
                'innmind/coding-standard',
                'innmind/colour',
                'innmind/crawler',
                'innmind/crawler-app',
                'innmind/cron',
                'innmind/debug',
                'innmind/dependency-graph',
                'innmind/di',
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
                'innmind/specification',
                'innmind/ssh-key-provider',
                'innmind/stack',
                'innmind/stack-trace',
                'innmind/stream',
                'innmind/templating',
                'innmind/time-continuum',
                'innmind/time-warp',
                'innmind/tower',
                'innmind/type',
                'innmind/url',
                'innmind/url-resolver',
                'innmind/url-template',
                'innmind/validation',
                'innmind/virtual-machine',
                'innmind/warden',
                'innmind/xml',
            ],
            $vendor
                ->packages()
                ->map(static fn($package) => $package->name()->toString())
                ->sort(static fn($a, $b) => $a <=> $b)
                ->toList(),
        );
    }

    public function testMaxOpenedFilesRegression()
    {
        $http = Curl::of(new Clock)->maxConcurrency(20);
        $load = new Vendor($http, new Package($http));

        $vendor = $load(Model\Name::of('symfony'));

        $this->assertInstanceOf(Model::class, $vendor);
        $this->assertCount(247, $vendor->packages());
    }
}
