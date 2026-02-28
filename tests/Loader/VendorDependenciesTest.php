<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\VendorDependencies,
    Loader\Vendor,
    Loader\Package,
    Vendor\Name,
};
use Innmind\HttpTransport\Transport;
use Innmind\Time\Clock;
use Innmind\Immutable\Set;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class VendorDependenciesTest extends TestCase
{
    public function testInvokation()
    {
        $http = Transport::curl(Clock::live())->map(
            static fn($config) => $config->limitConcurrencyTo(20),
        );
        $package = new Package($http);
        $load = new VendorDependencies(
            new Vendor($http, $package),
            $package,
        );

        $vendor = $load(Name::of('innmind'));

        $this->assertInstanceOf(Set::class, $vendor);
        $this->assertSame(
            [
                'amphp/cache',
                'amphp/dns',
                'amphp/socket',
                'composer/semver',
                'formal/access-layer',
                'friendsofphp/php-cs-fixer',
                'innmind/acl',
                'innmind/amqp',
                'innmind/async',
                'innmind/async-http-server',
                'innmind/black-box',
                'innmind/black-box-symfony',
                'innmind/cli',
                'innmind/cli-framework',
                'innmind/coding-standard',
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
                'innmind/genome',
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
                'innmind/socket',
                'innmind/specification',
                'innmind/stack-trace',
                'innmind/static-analysis',
                'innmind/time',
                'innmind/time-continuum',
                'innmind/type',
                'innmind/ui',
                'innmind/url',
                'innmind/url-resolver',
                'innmind/url-template',
                'innmind/validation',
                'innmind/virtual-machine',
                'innmind/warden',
                'innmind/xml',
                'monolog/monolog',
                'music-companion/apple-music',
                'phpunit/php-code-coverage',
                'phpunit/php-timer',
                'phpunit/phpunit',
                'psr/log',
                'ramsey/uuid',
                'symfony/browser-kit',
                'symfony/framework-bundle',
                'symfony/http-foundation',
                'symfony/http-kernel',
                'symfony/var-dumper',
                'vimeo/psalm',
            ],
            $vendor
                ->map(static fn($package) => $package->name()->toString())
                ->sort(static fn($a, $b) => $a <=> $b)
                ->toList(),
        );
    }
}
