<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Render,
    Loader\ComposerLock,
};
use Innmind\OperatingSystem\Filesystem\Generic;
use Innmind\Url\Path;
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
    psr__http_message [label="http-message"];
    psr__simple_cache [label="simple-cache"];
    }
    subgraph cluster_symfony {
        label="symfony"
    symfony__filesystem [label="filesystem"];
    symfony__finder [label="finder"];
    symfony__polyfill_ctype [label="polyfill-ctype"];
    }
    innmind__filesystem -> innmind__immutable;
    innmind__filesystem -> innmind__stream;
    innmind__filesystem -> symfony__filesystem;
    innmind__filesystem -> symfony__finder;
    innmind__stream -> innmind__immutable;
    innmind__stream -> innmind__time_continuum;
    symfony__filesystem -> symfony__polyfill_ctype;
    innmind__operating_system -> innmind__time_continuum;
    innmind__url -> innmind__immutable;
    innmind__url -> league__uri;
    league__uri -> league__uri_components;
    league__uri -> league__uri_hostname_parser;
    league__uri -> league__uri_interfaces;
    league__uri -> league__uri_manipulations;
    league__uri -> league__uri_parser;
    league__uri -> league__uri_schemes;
    league__uri -> psr__http_message;
    league__uri_components -> league__uri_hostname_parser;
    league__uri_hostname_parser -> psr__simple_cache;
    league__uri_manipulations -> league__uri_components;
    league__uri_manipulations -> league__uri_interfaces;
    league__uri_manipulations -> psr__http_message;
    league__uri_schemes -> league__uri_interfaces;
    league__uri_schemes -> league__uri_parser;
    league__uri_schemes -> psr__http_message;
    innmind__filesystem [URL="https://packagist.org/packages/innmind/filesystem"];
    innmind__immutable [URL="https://packagist.org/packages/innmind/immutable"];
    innmind__stream [URL="https://packagist.org/packages/innmind/stream"];
    symfony__filesystem [URL="https://packagist.org/packages/symfony/filesystem"];
    symfony__finder [URL="https://packagist.org/packages/symfony/finder"];
    innmind__json [URL="https://packagist.org/packages/innmind/json"];
    innmind__operating_system [URL="https://packagist.org/packages/innmind/operating-system"];
    innmind__time_continuum [URL="https://packagist.org/packages/innmind/time-continuum"];
    innmind__url [URL="https://packagist.org/packages/innmind/url"];
    league__uri [URL="https://packagist.org/packages/league/uri"];
    league__uri_components [URL="https://packagist.org/packages/league/uri-components"];
    league__uri_hostname_parser [URL="https://packagist.org/packages/league/uri-hostname-parser"];
    league__uri_interfaces [URL="https://packagist.org/packages/league/uri-interfaces"];
    league__uri_manipulations [URL="https://packagist.org/packages/league/uri-manipulations"];
    league__uri_parser [URL="https://packagist.org/packages/league/uri-parser"];
    league__uri_schemes [URL="https://packagist.org/packages/league/uri-schemes"];
    psr__http_message [URL="https://packagist.org/packages/psr/http-message"];
    psr__simple_cache [URL="https://packagist.org/packages/psr/simple-cache"];
    symfony__polyfill_ctype [URL="https://packagist.org/packages/symfony/polyfill-ctype"];
}
DOT;
        $this->assertSame($expected, (string) $stream);
    }
}
