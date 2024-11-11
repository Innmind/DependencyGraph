<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Dependencies,
    Loader\Package,
    Package as PackageModel,
    Render,
};
use Innmind\HttpTransport\Curl;
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class DependenciesTest extends TestCase
{
    public function testInvokation()
    {
        $load = new Dependencies(
            new Package(Curl::of(new Clock)->maxConcurrency(20)),
        );

        $packages = $load(PackageModel\Name::of('innmind/url'));

        $this->assertInstanceOf(Set::class, $packages);
        $this->assertCount(8, $packages);
        $expected = <<<DOT
digraph packages {
    subgraph cluster_innmind {
        label="innmind"
        URL="https://packagist.org/packages/innmind/"
    innmind__immutable [label="immutable@5.10.0"];
    innmind__url [label="url@4.3.2"];
    }
    subgraph cluster_league {
        label="league"
        URL="https://packagist.org/packages/league/"
    league__uri_parser [label="uri-parser@1.4.1"];
    league__uri_interfaces [label="uri-interfaces@7.4.1"];
    league__uri [label="uri@7.4.1"];
    league__uri_components [label="uri-components@7.4.1"];
    }
    subgraph cluster_psr {
        label="psr"
        URL="https://packagist.org/packages/psr/"
    psr__http_message [label="http-message@2.0"];
    psr__http_factory [label="http-factory@1.1.0"];
    }
    psr__http_factory -> psr__http_message [color="#8ac267", label="^1.0 || ^2.0"];
    league__uri_interfaces -> psr__http_message [color="#22ca7d", label="^1.1 || ^2.0"];
    league__uri_interfaces -> psr__http_factory [color="#22ca7d", label="^1"];
    league__uri -> league__uri_interfaces [color="#ef36b1", label="^7.3"];
    league__uri_components -> league__uri [color="#de64b9", label="^7.3"];
    innmind__url -> innmind__immutable [color="#085cd3", label="~4.15|~5.0"];
    innmind__url -> league__uri_parser [color="#085cd3", style="dotted", label="~1.2"];
    innmind__url -> league__uri_components [color="#ff0000", style="bold", label="~2.0"];
    innmind__immutable [shape="ellipse", width="0.75", height="0.5", color="#a7e599", URL="https://packagist.org/packages/innmind/immutable#5.10.0"];
    league__uri_parser [shape="ellipse", width="0.75", height="0.5", color="#bcf2f6", URL="https://packagist.org/packages/league/uri-parser#1.4.1"];
    psr__http_message [shape="ellipse", width="0.75", height="0.5", color="#8da3f1", URL="https://packagist.org/packages/psr/http-message#2.0"];
    psr__http_factory [shape="ellipse", width="0.75", height="0.5", color="#8ac267", URL="https://packagist.org/packages/psr/http-factory#1.1.0"];
    league__uri_interfaces [shape="ellipse", width="0.75", height="0.5", color="#22ca7d", URL="https://packagist.org/packages/league/uri-interfaces#7.4.1"];
    league__uri [shape="ellipse", width="0.75", height="0.5", color="#ef36b1", URL="https://packagist.org/packages/league/uri#7.4.1"];
    league__uri_components [shape="ellipse", width="0.75", height="0.5", color="#de64b9", URL="https://packagist.org/packages/league/uri-components#7.4.1"];
    innmind__url [shape="ellipse", width="0.75", height="0.5", color="#085cd3", URL="https://packagist.org/packages/innmind/url#4.3.2"];
}
DOT;

        $this->assertSame($expected, (new Render)($packages)->toString());
    }

    public function testDuplicatedRelationsRegression()
    {
        $load = new Dependencies(
            new Package(Curl::of(new Clock)->maxConcurrency(20)),
        );

        $packages = $load(PackageModel\Name::of('innmind/http-transport'));

        $this->assertInstanceOf(Set::class, $packages);
        $this->assertSame(
            [
                'brick/math',
                'innmind/filesystem',
                'innmind/http',
                'innmind/http-transport',
                'innmind/immutable',
                'innmind/io',
                'innmind/ip',
                'innmind/media-type',
                'innmind/socket',
                'innmind/stream',
                'innmind/time-continuum',
                'innmind/time-warp',
                'innmind/url',
                'league/uri',
                'league/uri-components',
                'league/uri-interfaces',
                'league/uri-parser',
                'psr/http-factory',
                'psr/http-message',
                'psr/log',
                'ramsey/collection',
                'ramsey/uuid',
                'symfony/filesystem',
                'symfony/polyfill-ctype',
                'symfony/polyfill-mbstring',
            ],
            $packages
                ->map(static fn($package) => $package->name()->toString())
                ->sort(static fn($a, $b) => $a <=> $b)
                ->toList(),
        );
    }

    public function testCircularDependencyRegression()
    {
        $load = new Dependencies(
            new Package(Curl::of(new Clock)->maxConcurrency(20)),
        );

        $packages = $load(PackageModel\Name::of('laravel/browser-kit-testing'));

        $this->assertInstanceOf(Set::class, $packages);
        $this->assertCount(101, $packages);
    }
}
