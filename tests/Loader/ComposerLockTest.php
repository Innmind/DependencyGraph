<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\ComposerLock,
    Package,
    Render,
};
use Innmind\OperatingSystem\Filesystem\Generic;
use Innmind\Url\Path;
use Innmind\Immutable\SetInterface;
use PHPUnit\Framework\TestCase;

class ComposerLockTest extends TestCase
{
    public function testInterface()
    {
        $load = new ComposerLock(new Generic);

        $packages = $load(new Path(__DIR__.'/../../fixtures'));

        $this->assertInstanceOf(SetInterface::class, $packages);
        $this->assertSame(Package::class, (string) $packages->type());
        $this->assertCount(19, $packages);
        $expected = <<<DOT
digraph packages {
    rankdir="LR";
    subgraph cluster_innmind {
        label="innmind"
        URL="https://packagist.org/packages/innmind/"
    innmind__filesystem [label="filesystem@3.3.0"];
    innmind__immutable [label="immutable@2.13.0"];
    innmind__json [label="json@1.1.0"];
    innmind__operating_system [label="operating-system@1.3.0"];
    innmind__stream [label="stream@1.4.0"];
    innmind__time_continuum [label="time-continuum@1.3.0"];
    innmind__url [label="url@2.0.3"];
    }
    subgraph cluster_league {
        label="league"
        URL="https://packagist.org/packages/league/"
    league__uri [label="uri@5.3.0"];
    league__uri_components [label="uri-components@1.8.2"];
    league__uri_hostname_parser [label="uri-hostname-parser@1.1.1"];
    league__uri_interfaces [label="uri-interfaces@1.1.1"];
    league__uri_manipulations [label="uri-manipulations@1.5.0"];
    league__uri_parser [label="uri-parser@1.4.1"];
    league__uri_schemes [label="uri-schemes@1.2.1"];
    }
    subgraph cluster_psr {
        label="psr"
        URL="https://packagist.org/packages/psr/"
    psr__http_message [label="http-message@1.0.1"];
    psr__simple_cache [label="simple-cache@1.0.1"];
    }
    subgraph cluster_symfony {
        label="symfony"
        URL="https://packagist.org/packages/symfony/"
    symfony__filesystem [label="filesystem@v4.2.2"];
    symfony__finder [label="finder@v4.2.2"];
    symfony__polyfill_ctype [label="polyfill-ctype@v1.10.0"];
    }
    innmind__filesystem -> innmind__immutable [color="#a45b8d"];
    innmind__filesystem -> innmind__stream [color="#a45b8d"];
    innmind__filesystem -> symfony__filesystem [color="#a45b8d"];
    innmind__filesystem -> symfony__finder [color="#a45b8d"];
    innmind__stream -> innmind__immutable [color="#5eb3ec"];
    innmind__stream -> innmind__time_continuum [color="#5eb3ec"];
    symfony__filesystem -> symfony__polyfill_ctype [color="#1a4d29"];
    innmind__operating_system -> innmind__time_continuum [color="#bb6188"];
    innmind__url -> innmind__immutable [color="#085cd3"];
    innmind__url -> league__uri [color="#085cd3"];
    league__uri -> league__uri_components [color="#ef36b1"];
    league__uri -> league__uri_hostname_parser [color="#ef36b1"];
    league__uri -> league__uri_interfaces [color="#ef36b1"];
    league__uri -> league__uri_manipulations [color="#ef36b1"];
    league__uri -> league__uri_parser [color="#ef36b1"];
    league__uri -> league__uri_schemes [color="#ef36b1"];
    league__uri -> psr__http_message [color="#ef36b1"];
    league__uri_components -> league__uri_hostname_parser [color="#de64b9"];
    league__uri_hostname_parser -> psr__simple_cache [color="#13807b"];
    league__uri_manipulations -> league__uri_components [color="#a0cfe9"];
    league__uri_manipulations -> league__uri_interfaces [color="#a0cfe9"];
    league__uri_manipulations -> psr__http_message [color="#a0cfe9"];
    league__uri_schemes -> league__uri_interfaces [color="#8cd717"];
    league__uri_schemes -> league__uri_parser [color="#8cd717"];
    league__uri_schemes -> psr__http_message [color="#8cd717"];
    innmind__filesystem [shape="ellipse", width="0.75", height="0.5", color="#a45b8d", URL="https://packagist.org/packages/innmind/filesystem#3.3.0"];
    innmind__immutable [shape="ellipse", width="0.75", height="0.5", color="#a7e599", URL="https://packagist.org/packages/innmind/immutable#2.13.0"];
    innmind__stream [shape="ellipse", width="0.75", height="0.5", color="#5eb3ec", URL="https://packagist.org/packages/innmind/stream#1.4.0"];
    symfony__filesystem [shape="ellipse", width="0.75", height="0.5", color="#1a4d29", URL="https://packagist.org/packages/symfony/filesystem#v4.2.2"];
    symfony__finder [shape="ellipse", width="0.75", height="0.5", color="#952a8a", URL="https://packagist.org/packages/symfony/finder#v4.2.2"];
    innmind__json [shape="ellipse", width="0.75", height="0.5", color="#cb2336", URL="https://packagist.org/packages/innmind/json#1.1.0"];
    innmind__operating_system [shape="ellipse", width="0.75", height="0.5", color="#bb6188", URL="https://packagist.org/packages/innmind/operating-system#1.3.0"];
    innmind__time_continuum [shape="ellipse", width="0.75", height="0.5", color="#dfbeb0", URL="https://packagist.org/packages/innmind/time-continuum#1.3.0"];
    innmind__url [shape="ellipse", width="0.75", height="0.5", color="#085cd3", URL="https://packagist.org/packages/innmind/url#2.0.3"];
    league__uri [shape="ellipse", width="0.75", height="0.5", color="#ef36b1", URL="https://packagist.org/packages/league/uri#5.3.0"];
    league__uri_components [shape="ellipse", width="0.75", height="0.5", color="#de64b9", URL="https://packagist.org/packages/league/uri-components#1.8.2"];
    league__uri_hostname_parser [shape="ellipse", width="0.75", height="0.5", color="#13807b", URL="https://packagist.org/packages/league/uri-hostname-parser#1.1.1"];
    league__uri_interfaces [shape="ellipse", width="0.75", height="0.5", color="#22ca7d", URL="https://packagist.org/packages/league/uri-interfaces#1.1.1"];
    league__uri_manipulations [shape="ellipse", width="0.75", height="0.5", color="#a0cfe9", URL="https://packagist.org/packages/league/uri-manipulations#1.5.0"];
    league__uri_parser [shape="ellipse", width="0.75", height="0.5", color="#bcf2f6", URL="https://packagist.org/packages/league/uri-parser#1.4.1"];
    league__uri_schemes [shape="ellipse", width="0.75", height="0.5", color="#8cd717", URL="https://packagist.org/packages/league/uri-schemes#1.2.1"];
    psr__http_message [shape="ellipse", width="0.75", height="0.5", color="#8da3f1", URL="https://packagist.org/packages/psr/http-message#1.0.1"];
    psr__simple_cache [shape="ellipse", width="0.75", height="0.5", color="#01186e", URL="https://packagist.org/packages/psr/simple-cache#1.0.1"];
    symfony__polyfill_ctype [shape="ellipse", width="0.75", height="0.5", color="#96e3a7", URL="https://packagist.org/packages/symfony/polyfill-ctype#v1.10.0"];
}
DOT;

        $this->assertSame($expected, (string) (new Render)(...$packages));
    }
}
