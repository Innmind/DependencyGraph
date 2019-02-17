<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Dependencies,
    Loader\Package,
    Package as PackageModel,
    Render,
};
use function Innmind\HttpTransport\bootstrap as http;
use Innmind\Immutable\SetInterface;
use PHPUnit\Framework\TestCase;

class DependenciesTest extends TestCase
{
    public function testInvokation()
    {
        $load = new Dependencies(
            new Package(http()['default']())
        );

        $packages = $load(PackageModel\Name::of('innmind/cli'));

        $this->assertInstanceOf(SetInterface::class, $packages);
        $this->assertSame(PackageModel::class, (string) $packages->type());
        $this->assertCount(25, $packages);
        $expected = <<<DOT
digraph packages {
    rankdir="LR";
    subgraph cluster_innmind {
        label="innmind"
        URL="https://packagist.org/packages/innmind/"
    innmind__cli [label="cli"];
    innmind__stream [label="stream"];
    innmind__immutable [label="immutable"];
    innmind__time_continuum [label="time-continuum"];
    innmind__url [label="url"];
    innmind__time_warp [label="time-warp"];
    innmind__operating_system [label="operating-system"];
    innmind__stack_trace [label="stack-trace"];
    innmind__graphviz [label="graphviz"];
    innmind__colour [label="colour"];
    innmind__filesystem [label="filesystem"];
    }
    subgraph cluster_league {
        label="league"
        URL="https://packagist.org/packages/league/"
    league__uri [label="uri"];
    league__uri_interfaces [label="uri-interfaces"];
    league__uri_components [label="uri-components"];
    league__uri_query_parser [label="uri-query-parser"];
    league__uri_hostname_parser [label="uri-hostname-parser"];
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
    symfony__polyfill_ctype [label="polyfill-ctype"];
    symfony__finder [label="finder"];
    symfony__dotenv [label="dotenv"];
    }
    innmind__cli -> innmind__stream [color="#87dfa2"];
    innmind__cli -> innmind__immutable [color="#87dfa2"];
    innmind__cli -> innmind__url [color="#87dfa2"];
    innmind__cli -> innmind__time_warp [color="#87dfa2"];
    innmind__cli -> innmind__operating_system [color="#87dfa2"];
    innmind__cli -> innmind__stack_trace [color="#87dfa2"];
    innmind__cli -> innmind__filesystem [color="#87dfa2"];
    innmind__cli -> symfony__dotenv [color="#87dfa2"];
    innmind__stream -> innmind__immutable [color="#5eb3ec"];
    innmind__stream -> innmind__time_continuum [color="#5eb3ec"];
    innmind__url -> innmind__immutable [color="#085cd3"];
    innmind__url -> league__uri [color="#085cd3"];
    innmind__time_warp -> innmind__time_continuum [color="#e567f2"];
    innmind__operating_system -> innmind__time_continuum [color="#bb6188"];
    innmind__stack_trace -> innmind__immutable [color="#d73f0b"];
    innmind__stack_trace -> innmind__url [color="#d73f0b"];
    innmind__stack_trace -> innmind__graphviz [color="#d73f0b"];
    innmind__filesystem -> innmind__stream [color="#a45b8d"];
    innmind__filesystem -> symfony__filesystem [color="#a45b8d"];
    innmind__filesystem -> symfony__finder [color="#a45b8d"];
    innmind__filesystem -> innmind__immutable [color="#a45b8d"];
    league__uri -> league__uri_interfaces [color="#ef36b1"];
    league__uri -> psr__http_message [color="#ef36b1"];
    league__uri -> league__uri_components [color="#ef36b1"];
    league__uri -> league__uri_hostname_parser [color="#ef36b1"];
    league__uri -> league__uri_manipulations [color="#ef36b1"];
    league__uri -> league__uri_parser [color="#ef36b1"];
    league__uri -> league__uri_schemes [color="#ef36b1"];
    league__uri_components -> league__uri_interfaces [color="#de64b9"];
    league__uri_components -> psr__http_message [color="#de64b9"];
    league__uri_components -> league__uri_query_parser [color="#de64b9"];
    league__uri_hostname_parser -> psr__simple_cache [color="#13807b"];
    league__uri_manipulations -> psr__http_message [color="#a0cfe9"];
    league__uri_manipulations -> league__uri_components [color="#a0cfe9"];
    league__uri_manipulations -> league__uri_interfaces [color="#a0cfe9"];
    league__uri_schemes -> psr__http_message [color="#8cd717"];
    league__uri_schemes -> league__uri_parser [color="#8cd717"];
    innmind__graphviz -> innmind__immutable [color="#39df6f"];
    innmind__graphviz -> innmind__url [color="#39df6f"];
    innmind__graphviz -> innmind__colour [color="#39df6f"];
    innmind__graphviz -> innmind__stream [color="#39df6f"];
    innmind__colour -> innmind__immutable [color="#356a4c"];
    symfony__filesystem -> symfony__polyfill_ctype [color="#1a4d29"];
    innmind__cli [shape="ellipse", width="0.75", height="0.5", color="#87dfa2", URL="https://packagist.org/packages/innmind/cli"];
    innmind__stream [shape="ellipse", width="0.75", height="0.5", color="#5eb3ec", URL="https://packagist.org/packages/innmind/stream"];
    innmind__immutable [shape="ellipse", width="0.75", height="0.5", color="#a7e599", URL="https://packagist.org/packages/innmind/immutable"];
    innmind__url [shape="ellipse", width="0.75", height="0.5", color="#085cd3", URL="https://packagist.org/packages/innmind/url"];
    innmind__time_warp [shape="ellipse", width="0.75", height="0.5", color="#e567f2", URL="https://packagist.org/packages/innmind/time-warp"];
    innmind__operating_system [shape="ellipse", width="0.75", height="0.5", color="#bb6188", URL="https://packagist.org/packages/innmind/operating-system"];
    innmind__stack_trace [shape="ellipse", width="0.75", height="0.5", color="#d73f0b", URL="https://packagist.org/packages/innmind/stack-trace"];
    innmind__filesystem [shape="ellipse", width="0.75", height="0.5", color="#a45b8d", URL="https://packagist.org/packages/innmind/filesystem"];
    symfony__dotenv [shape="ellipse", width="0.75", height="0.5", color="#681457", URL="https://packagist.org/packages/symfony/dotenv"];
    innmind__time_continuum [shape="ellipse", width="0.75", height="0.5", color="#dfbeb0", URL="https://packagist.org/packages/innmind/time-continuum"];
    league__uri [shape="ellipse", width="0.75", height="0.5", color="#ef36b1", URL="https://packagist.org/packages/league/uri"];
    league__uri_interfaces [shape="ellipse", width="0.75", height="0.5", color="#22ca7d", URL="https://packagist.org/packages/league/uri-interfaces"];
    psr__http_message [shape="ellipse", width="0.75", height="0.5", color="#8da3f1", URL="https://packagist.org/packages/psr/http-message"];
    league__uri_components [shape="ellipse", width="0.75", height="0.5", color="#de64b9", URL="https://packagist.org/packages/league/uri-components"];
    league__uri_hostname_parser [shape="ellipse", width="0.75", height="0.5", color="#13807b", URL="https://packagist.org/packages/league/uri-hostname-parser"];
    league__uri_manipulations [shape="ellipse", width="0.75", height="0.5", color="#a0cfe9", URL="https://packagist.org/packages/league/uri-manipulations"];
    league__uri_parser [shape="ellipse", width="0.75", height="0.5", color="#bcf2f6", URL="https://packagist.org/packages/league/uri-parser"];
    league__uri_schemes [shape="ellipse", width="0.75", height="0.5", color="#8cd717", URL="https://packagist.org/packages/league/uri-schemes"];
    league__uri_query_parser [shape="ellipse", width="0.75", height="0.5", color="#1e8c91", URL="https://packagist.org/packages/league/uri-query-parser"];
    psr__simple_cache [shape="ellipse", width="0.75", height="0.5", color="#01186e", URL="https://packagist.org/packages/psr/simple-cache"];
    innmind__graphviz [shape="ellipse", width="0.75", height="0.5", color="#39df6f", URL="https://packagist.org/packages/innmind/graphviz"];
    innmind__colour [shape="ellipse", width="0.75", height="0.5", color="#356a4c", URL="https://packagist.org/packages/innmind/colour"];
    symfony__filesystem [shape="ellipse", width="0.75", height="0.5", color="#1a4d29", URL="https://packagist.org/packages/symfony/filesystem"];
    symfony__finder [shape="ellipse", width="0.75", height="0.5", color="#952a8a", URL="https://packagist.org/packages/symfony/finder"];
    symfony__polyfill_ctype [shape="ellipse", width="0.75", height="0.5", color="#96e3a7", URL="https://packagist.org/packages/symfony/polyfill-ctype"];
}
DOT;

        $this->assertSame($expected, (string) (new Render)(...$packages));
    }
}
