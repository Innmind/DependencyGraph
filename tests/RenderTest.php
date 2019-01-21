<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Render,
    Render\Locate,
    Loader\ComposerLock,
    Package,
};
use Innmind\OperatingSystem\Filesystem\Generic;
use Innmind\Url\{
    UrlInterface,
    Path,
    Scheme,
};
use Innmind\Stream\Readable;
use PHPUnit\Framework\TestCase;

class RenderTest extends TestCase
{
    public function testInvokation()
    {
        $render = new Render;
        $packages = (new ComposerLock(new Generic))(new Path(__DIR__.'/../fixtures'));

        $stream = $render(...$packages);

        $this->assertInstanceOf(Readable::class, $stream);
        $expected = <<<DOT
digraph packages {
    rankdir="LR";
    subgraph cluster_innmind {
        label="innmind"
        URL="https://packagist.org/packages/innmind/"
    innmind__filesystem [label="filesystem"];
    innmind__immutable [label="immutable"];
    innmind__json [label="json"];
    innmind__operating_system [label="operating-system"];
    innmind__stream [label="stream"];
    innmind__time_continuum [label="time-continuum"];
    innmind__url [label="url"];
    }
    subgraph cluster_league {
        label="league"
        URL="https://packagist.org/packages/league/"
    league__uri [label="uri"];
    league__uri_components [label="uri-components"];
    league__uri_hostname_parser [label="uri-hostname-parser"];
    league__uri_interfaces [label="uri-interfaces"];
    league__uri_manipulations [label="uri-manipulations"];
    league__uri_parser [label="uri-parser"];
    league__uri_schemes [label="uri-schemes"];
    }
    subgraph cluster_psr {
        label="psr"
        URL="https://packagist.org/packages/psr/"
    psr__http_message [label="http-message"];
    psr__simple_cache [label="simple-cache"];
    }
    subgraph cluster_symfony {
        label="symfony"
        URL="https://packagist.org/packages/symfony/"
    symfony__filesystem [label="filesystem"];
    symfony__finder [label="finder"];
    symfony__polyfill_ctype [label="polyfill-ctype"];
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
    innmind__filesystem [shape="ellipse", width="0.75", height="0.5", color="#a45b8d", URL="https://packagist.org/packages/innmind/filesystem"];
    innmind__immutable [shape="ellipse", width="0.75", height="0.5", color="#a7e599", URL="https://packagist.org/packages/innmind/immutable"];
    innmind__stream [shape="ellipse", width="0.75", height="0.5", color="#5eb3ec", URL="https://packagist.org/packages/innmind/stream"];
    symfony__filesystem [shape="ellipse", width="0.75", height="0.5", color="#1a4d29", URL="https://packagist.org/packages/symfony/filesystem"];
    symfony__finder [shape="ellipse", width="0.75", height="0.5", color="#952a8a", URL="https://packagist.org/packages/symfony/finder"];
    innmind__json [shape="ellipse", width="0.75", height="0.5", color="#cb2336", URL="https://packagist.org/packages/innmind/json"];
    innmind__operating_system [shape="ellipse", width="0.75", height="0.5", color="#bb6188", URL="https://packagist.org/packages/innmind/operating-system"];
    innmind__time_continuum [shape="ellipse", width="0.75", height="0.5", color="#dfbeb0", URL="https://packagist.org/packages/innmind/time-continuum"];
    innmind__url [shape="ellipse", width="0.75", height="0.5", color="#085cd3", URL="https://packagist.org/packages/innmind/url"];
    league__uri [shape="ellipse", width="0.75", height="0.5", color="#ef36b1", URL="https://packagist.org/packages/league/uri"];
    league__uri_components [shape="ellipse", width="0.75", height="0.5", color="#de64b9", URL="https://packagist.org/packages/league/uri-components"];
    league__uri_hostname_parser [shape="ellipse", width="0.75", height="0.5", color="#13807b", URL="https://packagist.org/packages/league/uri-hostname-parser"];
    league__uri_interfaces [shape="ellipse", width="0.75", height="0.5", color="#22ca7d", URL="https://packagist.org/packages/league/uri-interfaces"];
    league__uri_manipulations [shape="ellipse", width="0.75", height="0.5", color="#a0cfe9", URL="https://packagist.org/packages/league/uri-manipulations"];
    league__uri_parser [shape="ellipse", width="0.75", height="0.5", color="#bcf2f6", URL="https://packagist.org/packages/league/uri-parser"];
    league__uri_schemes [shape="ellipse", width="0.75", height="0.5", color="#8cd717", URL="https://packagist.org/packages/league/uri-schemes"];
    psr__http_message [shape="ellipse", width="0.75", height="0.5", color="#8da3f1", URL="https://packagist.org/packages/psr/http-message"];
    psr__simple_cache [shape="ellipse", width="0.75", height="0.5", color="#01186e", URL="https://packagist.org/packages/psr/simple-cache"];
    symfony__polyfill_ctype [shape="ellipse", width="0.75", height="0.5", color="#96e3a7", URL="https://packagist.org/packages/symfony/polyfill-ctype"];
}
DOT;
        $this->assertSame($expected, (string) $stream);
    }

    public function testLocateToRepository()
    {
        $render = new Render(new class implements Locate {
            public function __invoke(Package $package): UrlInterface
            {
                return $package->packagist()->withScheme(new Scheme('foo'));
            }
        });
        $packages = (new ComposerLock(new Generic))(new Path(__DIR__.'/../fixtures'));

        $stream = $render(...$packages);

        $this->assertInstanceOf(Readable::class, $stream);
        $expected = <<<DOT
digraph packages {
    rankdir="LR";
    subgraph cluster_innmind {
        label="innmind"
        URL="https://packagist.org/packages/innmind/"
    innmind__filesystem [label="filesystem"];
    innmind__immutable [label="immutable"];
    innmind__json [label="json"];
    innmind__operating_system [label="operating-system"];
    innmind__stream [label="stream"];
    innmind__time_continuum [label="time-continuum"];
    innmind__url [label="url"];
    }
    subgraph cluster_league {
        label="league"
        URL="https://packagist.org/packages/league/"
    league__uri [label="uri"];
    league__uri_components [label="uri-components"];
    league__uri_hostname_parser [label="uri-hostname-parser"];
    league__uri_interfaces [label="uri-interfaces"];
    league__uri_manipulations [label="uri-manipulations"];
    league__uri_parser [label="uri-parser"];
    league__uri_schemes [label="uri-schemes"];
    }
    subgraph cluster_psr {
        label="psr"
        URL="https://packagist.org/packages/psr/"
    psr__http_message [label="http-message"];
    psr__simple_cache [label="simple-cache"];
    }
    subgraph cluster_symfony {
        label="symfony"
        URL="https://packagist.org/packages/symfony/"
    symfony__filesystem [label="filesystem"];
    symfony__finder [label="finder"];
    symfony__polyfill_ctype [label="polyfill-ctype"];
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
    innmind__filesystem [shape="ellipse", width="0.75", height="0.5", color="#a45b8d", URL="foo://packagist.org/packages/innmind/filesystem"];
    innmind__immutable [shape="ellipse", width="0.75", height="0.5", color="#a7e599", URL="foo://packagist.org/packages/innmind/immutable"];
    innmind__stream [shape="ellipse", width="0.75", height="0.5", color="#5eb3ec", URL="foo://packagist.org/packages/innmind/stream"];
    symfony__filesystem [shape="ellipse", width="0.75", height="0.5", color="#1a4d29", URL="foo://packagist.org/packages/symfony/filesystem"];
    symfony__finder [shape="ellipse", width="0.75", height="0.5", color="#952a8a", URL="foo://packagist.org/packages/symfony/finder"];
    innmind__json [shape="ellipse", width="0.75", height="0.5", color="#cb2336", URL="foo://packagist.org/packages/innmind/json"];
    innmind__operating_system [shape="ellipse", width="0.75", height="0.5", color="#bb6188", URL="foo://packagist.org/packages/innmind/operating-system"];
    innmind__time_continuum [shape="ellipse", width="0.75", height="0.5", color="#dfbeb0", URL="foo://packagist.org/packages/innmind/time-continuum"];
    innmind__url [shape="ellipse", width="0.75", height="0.5", color="#085cd3", URL="foo://packagist.org/packages/innmind/url"];
    league__uri [shape="ellipse", width="0.75", height="0.5", color="#ef36b1", URL="foo://packagist.org/packages/league/uri"];
    league__uri_components [shape="ellipse", width="0.75", height="0.5", color="#de64b9", URL="foo://packagist.org/packages/league/uri-components"];
    league__uri_hostname_parser [shape="ellipse", width="0.75", height="0.5", color="#13807b", URL="foo://packagist.org/packages/league/uri-hostname-parser"];
    league__uri_interfaces [shape="ellipse", width="0.75", height="0.5", color="#22ca7d", URL="foo://packagist.org/packages/league/uri-interfaces"];
    league__uri_manipulations [shape="ellipse", width="0.75", height="0.5", color="#a0cfe9", URL="foo://packagist.org/packages/league/uri-manipulations"];
    league__uri_parser [shape="ellipse", width="0.75", height="0.5", color="#bcf2f6", URL="foo://packagist.org/packages/league/uri-parser"];
    league__uri_schemes [shape="ellipse", width="0.75", height="0.5", color="#8cd717", URL="foo://packagist.org/packages/league/uri-schemes"];
    psr__http_message [shape="ellipse", width="0.75", height="0.5", color="#8da3f1", URL="foo://packagist.org/packages/psr/http-message"];
    psr__simple_cache [shape="ellipse", width="0.75", height="0.5", color="#01186e", URL="foo://packagist.org/packages/psr/simple-cache"];
    symfony__polyfill_ctype [shape="ellipse", width="0.75", height="0.5", color="#96e3a7", URL="foo://packagist.org/packages/symfony/polyfill-ctype"];
}
DOT;
        $this->assertSame($expected, (string) $stream);
    }
}
