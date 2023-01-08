<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\VendorDependencies,
    Loader\Vendor,
    Loader\Package,
    Vendor\Name,
    Package as Model,
};
use Innmind\HttpTransport\Curl;
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class VendorDependenciesTest extends TestCase
{
    public function testInvokation()
    {
        $http = Curl::of(new Clock);
        $package = new Package($http);
        $load = new VendorDependencies(
            new Vendor($http, $package),
            $package,
        );

        $vendor = $load(Name::of('innmind'));

        $this->assertInstanceOf(Set::class, $vendor);
        $this->assertSame(
            [
                'aws/aws-sdk-php',
                'composer/semver',
                'doctrine/orm',
                'formal/access-layer',
                'friendsofphp/php-cs-fixer',
                'innmind/acl',
                'innmind/amqp',
                'innmind/ark',
                'innmind/black-box',
                'innmind/cli',
                'innmind/cli-framework',
                'innmind/coding-standard',
                'innmind/colour',
                'innmind/command-bus',
                'innmind/crawler',
                'innmind/crawler-app',
                'innmind/cron',
                'innmind/debug',
                'innmind/dependency-graph',
                'innmind/di',
                'innmind/doctrine',
                'innmind/event-bus',
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
                'innmind/http-framework',
                'innmind/http-server',
                'innmind/http-session',
                'innmind/http-transport',
                'innmind/immutable',
                'innmind/infrastructure',
                'innmind/infrastructure-amqp',
                'innmind/infrastructure-neo4j',
                'innmind/infrastructure-nginx',
                'innmind/installation-monitor',
                'innmind/ip',
                'innmind/ipc',
                'innmind/json',
                'innmind/kalmiya',
                'innmind/lab-station',
                'innmind/library',
                'innmind/log-reader',
                'innmind/logger',
                'innmind/math',
                'innmind/media-type',
                'innmind/neo4j-dbal',
                'innmind/neo4j-onm',
                'innmind/object-graph',
                'innmind/operating-system',
                'innmind/process-manager',
                'innmind/profiler',
                'innmind/rabbitmq-management',
                'innmind/reflection',
                'innmind/rest-client',
                'innmind/rest-server',
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
                'innmind/url',
                'innmind/url-resolver',
                'innmind/url-template',
                'innmind/virtual-machine',
                'innmind/warden',
                'innmind/xml',
                'jeremykendall/php-domain-parser',
                'league/uri-components',
                'league/uri-parser',
                'monolog/monolog',
                'music-companion/apple-music',
                'ovh/ovh',
                'phpunit/phpunit',
                'psr/log',
                'ramsey/collection',
                'ramsey/uuid',
                'symfony/config',
                'symfony/dom-crawler',
                'symfony/dotenv',
                'symfony/filesystem',
                'symfony/process',
                'symfony/var-dumper',
                'symfony/yaml',
                'twig/twig',
                'willdurand/negotiation',
            ],
            $vendor
                ->map(static fn($package) => $package->name()->toString())
                ->sort(static fn($a, $b) => $a <=> $b)
                ->toList(),
        );
    }
}
