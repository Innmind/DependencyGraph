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
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class DependenciesTest extends TestCase
{
    public function testInvokation()
    {
        $load = new Dependencies(
            new Package(http()['default']())
        );

        $packages = $load(PackageModel\Name::of('innmind/cli'));

        $this->assertInstanceOf(Set::class, $packages);
        $this->assertSame(PackageModel::class, (string) $packages->type());
        $this->assertCount(36, $packages);
        $expected = <<<DOT
digraph packages {
    subgraph cluster_innmind {
        label="innmind"
        URL="https://packagist.org/packages/innmind/"
    innmind__cli [label="cli@2.0.0"];
    innmind__stream [label="stream@2.0.0"];
    innmind__immutable [label="immutable@3.5.1"];
    innmind__time_continuum [label="time-continuum@2.2.0"];
    innmind__url [label="url@3.3.0"];
    innmind__operating_system [label="operating-system@2.0.0"];
    innmind__server_status [label="server-status@2.0.2"];
    innmind__server_control [label="server-control@3.0.0"];
    innmind__filesystem [label="filesystem@4.0.0"];
    innmind__media_type [label="media-type@1.2.0"];
    innmind__socket [label="socket@3.0.0"];
    innmind__event_bus [label="event-bus@4.0.0"];
    innmind__ip [label="ip@2.0.0"];
    innmind__http_transport [label="http-transport@5.0.1"];
    innmind__http [label="http@4.2.0"];
    innmind__time_warp [label="time-warp@2.0.0"];
    innmind__signals [label="signals@2.0.0"];
    innmind__file_watch [label="file-watch@2.0.0"];
    innmind__stack_trace [label="stack-trace@3.0.0"];
    innmind__graphviz [label="graphviz@2.0.0"];
    innmind__colour [label="colour@3.1.0"];
    }
    subgraph cluster_league {
        label="league"
        URL="https://packagist.org/packages/league/"
    league__uri [label="uri@6.2.0"];
    league__uri_interfaces [label="uri-interfaces@2.1.0"];
    }
    subgraph cluster_psr {
        label="psr"
        URL="https://packagist.org/packages/psr/"
    psr__http_message [label="http-message@1.0.1"];
    psr__log [label="log@1.1.2"];
    }
    subgraph cluster_symfony {
        label="symfony"
        URL="https://packagist.org/packages/symfony/"
    symfony__process [label="process@v5.0.5"];
    symfony__filesystem [label="filesystem@v5.0.5"];
    symfony__polyfill_ctype [label="polyfill-ctype@v1.14.0"];
    symfony__finder [label="finder@v5.0.5"];
    symfony__dotenv [label="dotenv@v5.0.5"];
    }
    subgraph cluster_guzzlehttp {
        label="guzzlehttp"
        URL="https://packagist.org/packages/guzzlehttp/"
    guzzlehttp__psr7 [label="psr7@1.6.1"];
    guzzlehttp__guzzle [label="guzzle@6.5.2"];
    guzzlehttp__promises [label="promises@v1.3.1"];
    }
    subgraph cluster_ralouphie {
        label="ralouphie"
        URL="https://packagist.org/packages/ralouphie/"
    ralouphie__getallheaders [label="getallheaders@3.0.3"];
    }
    subgraph cluster_ramsey {
        label="ramsey"
        URL="https://packagist.org/packages/ramsey/"
    ramsey__uuid [label="uuid@3.9.3"];
    }
    subgraph cluster_paragonie {
        label="paragonie"
        URL="https://packagist.org/packages/paragonie/"
    paragonie__random_compat [label="random_compat@v9.99.99"];
    }
    innmind__cli -> innmind__stream [color="#87dfa2", label="~2.0"];
    innmind__cli -> innmind__immutable [color="#87dfa2", label="~3.3"];
    innmind__cli -> innmind__url [color="#87dfa2", label="~3.0"];
    innmind__cli -> innmind__operating_system [color="#87dfa2", label="~2.0"];
    innmind__cli -> innmind__stack_trace [color="#87dfa2", label="~3.0"];
    innmind__cli -> symfony__dotenv [color="#87dfa2", label="~5.0"];
    innmind__stream -> innmind__immutable [color="#5eb3ec", label="~3.0"];
    innmind__stream -> innmind__time_continuum [color="#5eb3ec", label="~2.0"];
    innmind__stream -> innmind__url [color="#5eb3ec", label="^3.0"];
    innmind__url -> innmind__immutable [color="#085cd3", label="~3.0"];
    innmind__url -> league__uri [color="#ff0000", label="~5.0", style="bold"];
    innmind__operating_system -> innmind__time_continuum [color="#bb6188", label="~2.0"];
    innmind__operating_system -> innmind__server_status [color="#bb6188", label="~2.0"];
    innmind__operating_system -> innmind__server_control [color="#bb6188", label="~3.0"];
    innmind__operating_system -> innmind__filesystem [color="#bb6188", label="~4.0"];
    innmind__operating_system -> innmind__socket [color="#bb6188", label="~3.0"];
    innmind__operating_system -> innmind__http_transport [color="#bb6188", label="~5.0"];
    innmind__operating_system -> innmind__time_warp [color="#bb6188", label="~2.0"];
    innmind__operating_system -> innmind__signals [color="#bb6188", label="~2.0"];
    innmind__operating_system -> innmind__file_watch [color="#bb6188", label="~2.0"];
    innmind__stack_trace -> innmind__immutable [color="#d73f0b", label="~3.0"];
    innmind__stack_trace -> innmind__url [color="#d73f0b", label="~3.0"];
    innmind__stack_trace -> innmind__graphviz [color="#d73f0b", label="~2.0"];
    league__uri -> psr__http_message [color="#ef36b1", label="^1.0"];
    league__uri -> league__uri_interfaces [color="#ef36b1", label="^2.1"];
    innmind__server_status -> innmind__immutable [color="#523e48", label="~3.0"];
    innmind__server_status -> innmind__time_continuum [color="#523e48", label="~2.0"];
    innmind__server_status -> symfony__process [color="#523e48", label="~4.0|~5.0"];
    innmind__server_status -> innmind__url [color="#523e48", label="~3.0"];
    innmind__server_control -> innmind__immutable [color="#43d797", label="~3.0"];
    innmind__server_control -> symfony__process [color="#43d797", label="~4.0|~5.0"];
    innmind__server_control -> innmind__stream [color="#43d797", label="~2.0"];
    innmind__server_control -> innmind__url [color="#43d797", label="~3.0"];
    innmind__server_control -> psr__log [color="#43d797", label="^1.0"];
    innmind__filesystem -> innmind__immutable [color="#a45b8d", label="~3.0"];
    innmind__filesystem -> symfony__filesystem [color="#a45b8d", label="^3.0|~4.0|~5.0"];
    innmind__filesystem -> symfony__finder [color="#a45b8d", label="~3.0|~4.0|~5.0"];
    innmind__filesystem -> innmind__stream [color="#a45b8d", label="~2.0"];
    innmind__filesystem -> innmind__media_type [color="#a45b8d", label="^1.1"];
    innmind__filesystem -> innmind__url [color="#a45b8d", label="^3.1"];
    innmind__socket -> innmind__stream [color="#6b5f49", label="~2.0"];
    innmind__socket -> innmind__immutable [color="#6b5f49", label="~3.0"];
    innmind__socket -> innmind__event_bus [color="#6b5f49", label="~4.0"];
    innmind__socket -> innmind__ip [color="#6b5f49", label="~2.0"];
    innmind__socket -> innmind__url [color="#6b5f49", label="~3.0"];
    innmind__http_transport -> innmind__http [color="#d9c72a", label="~4.0"];
    innmind__http_transport -> guzzlehttp__guzzle [color="#d9c72a", label="^6.2"];
    innmind__http_transport -> psr__log [color="#d9c72a", label="^1.0"];
    innmind__http_transport -> ramsey__uuid [color="#d9c72a", label="^3.5"];
    innmind__http_transport -> innmind__time_warp [color="#d9c72a", label="~2.0"];
    innmind__http_transport -> innmind__time_continuum [color="#d9c72a", label="~2.0"];
    innmind__time_warp -> innmind__time_continuum [color="#e567f2", label="~2.0"];
    innmind__signals -> innmind__immutable [color="#67d973", label="~3.0"];
    innmind__file_watch -> innmind__url [color="#23b335", label="~3.0"];
    innmind__file_watch -> innmind__server_control [color="#23b335", label="~3.0"];
    innmind__file_watch -> innmind__time_warp [color="#23b335", label="~2.0"];
    innmind__file_watch -> innmind__time_continuum [color="#23b335", label="~2.0"];
    symfony__filesystem -> symfony__polyfill_ctype [color="#1a4d29", label="~1.8"];
    innmind__media_type -> innmind__immutable [color="#4daafe", label="^3.0"];
    innmind__event_bus -> innmind__immutable [color="#2d0232", label="~3.0"];
    innmind__http -> innmind__url [color="#324178", label="~3.0"];
    innmind__http -> innmind__immutable [color="#324178", label="~3.0"];
    innmind__http -> innmind__filesystem [color="#324178", label="~4.0"];
    innmind__http -> innmind__time_continuum [color="#324178", label="~2.0"];
    innmind__http -> innmind__stream [color="#324178", label="~2.0"];
    innmind__http -> guzzlehttp__psr7 [color="#324178", label="^1.6"];
    guzzlehttp__guzzle -> guzzlehttp__promises [color="#e5a30c", label="^1.0"];
    guzzlehttp__guzzle -> guzzlehttp__psr7 [color="#e5a30c", label="^1.6.1"];
    ramsey__uuid -> paragonie__random_compat [color="#44619b", label="^1 | ^2 | 9.99.99"];
    ramsey__uuid -> symfony__polyfill_ctype [color="#44619b", label="^1.8"];
    guzzlehttp__psr7 -> psr__http_message [color="#adcacd", label="~1.0"];
    guzzlehttp__psr7 -> ralouphie__getallheaders [color="#adcacd", label="^2.0.5 || ^3.0.0"];
    innmind__graphviz -> innmind__immutable [color="#39df6f", label="~3.0"];
    innmind__graphviz -> innmind__url [color="#39df6f", label="~3.0"];
    innmind__graphviz -> innmind__colour [color="#39df6f", label="~3.0"];
    innmind__graphviz -> innmind__stream [color="#39df6f", label="~2.0"];
    innmind__colour -> innmind__immutable [color="#356a4c", label="~3.0"];
    innmind__cli [shape="ellipse", width="0.75", height="0.5", color="#87dfa2", URL="https://packagist.org/packages/innmind/cli#2.0.0"];
    innmind__stream [shape="ellipse", width="0.75", height="0.5", color="#5eb3ec", URL="https://packagist.org/packages/innmind/stream#2.0.0"];
    innmind__immutable [shape="ellipse", width="0.75", height="0.5", color="#a7e599", URL="https://packagist.org/packages/innmind/immutable#3.5.1"];
    innmind__url [shape="ellipse", width="0.75", height="0.5", color="#085cd3", URL="https://packagist.org/packages/innmind/url#3.3.0"];
    innmind__operating_system [shape="ellipse", width="0.75", height="0.5", color="#bb6188", URL="https://packagist.org/packages/innmind/operating-system#2.0.0"];
    innmind__stack_trace [shape="ellipse", width="0.75", height="0.5", color="#d73f0b", URL="https://packagist.org/packages/innmind/stack-trace#3.0.0"];
    symfony__dotenv [shape="ellipse", width="0.75", height="0.5", color="#681457", URL="https://packagist.org/packages/symfony/dotenv#v5.0.5"];
    innmind__time_continuum [shape="ellipse", width="0.75", height="0.5", color="#dfbeb0", URL="https://packagist.org/packages/innmind/time-continuum#2.2.0"];
    league__uri [shape="ellipse", width="0.75", height="0.5", color="#ef36b1", URL="https://packagist.org/packages/league/uri#6.2.0"];
    psr__http_message [shape="ellipse", width="0.75", height="0.5", color="#8da3f1", URL="https://packagist.org/packages/psr/http-message#1.0.1"];
    league__uri_interfaces [shape="ellipse", width="0.75", height="0.5", color="#22ca7d", URL="https://packagist.org/packages/league/uri-interfaces#2.1.0"];
    innmind__server_status [shape="ellipse", width="0.75", height="0.5", color="#523e48", URL="https://packagist.org/packages/innmind/server-status#2.0.2"];
    innmind__server_control [shape="ellipse", width="0.75", height="0.5", color="#43d797", URL="https://packagist.org/packages/innmind/server-control#3.0.0"];
    innmind__filesystem [shape="ellipse", width="0.75", height="0.5", color="#a45b8d", URL="https://packagist.org/packages/innmind/filesystem#4.0.0"];
    innmind__socket [shape="ellipse", width="0.75", height="0.5", color="#6b5f49", URL="https://packagist.org/packages/innmind/socket#3.0.0"];
    innmind__http_transport [shape="ellipse", width="0.75", height="0.5", color="#d9c72a", URL="https://packagist.org/packages/innmind/http-transport#5.0.1"];
    innmind__time_warp [shape="ellipse", width="0.75", height="0.5", color="#e567f2", URL="https://packagist.org/packages/innmind/time-warp#2.0.0"];
    innmind__signals [shape="ellipse", width="0.75", height="0.5", color="#67d973", URL="https://packagist.org/packages/innmind/signals#2.0.0"];
    innmind__file_watch [shape="ellipse", width="0.75", height="0.5", color="#23b335", URL="https://packagist.org/packages/innmind/file-watch#2.0.0"];
    symfony__process [shape="ellipse", width="0.75", height="0.5", color="#9cadc5", URL="https://packagist.org/packages/symfony/process#v5.0.5"];
    psr__log [shape="ellipse", width="0.75", height="0.5", color="#e9a8b6", URL="https://packagist.org/packages/psr/log#1.1.2"];
    symfony__filesystem [shape="ellipse", width="0.75", height="0.5", color="#1a4d29", URL="https://packagist.org/packages/symfony/filesystem#v5.0.5"];
    symfony__finder [shape="ellipse", width="0.75", height="0.5", color="#952a8a", URL="https://packagist.org/packages/symfony/finder#v5.0.5"];
    innmind__media_type [shape="ellipse", width="0.75", height="0.5", color="#4daafe", URL="https://packagist.org/packages/innmind/media-type#1.2.0"];
    symfony__polyfill_ctype [shape="ellipse", width="0.75", height="0.5", color="#96e3a7", URL="https://packagist.org/packages/symfony/polyfill-ctype#v1.14.0"];
    innmind__event_bus [shape="ellipse", width="0.75", height="0.5", color="#2d0232", URL="https://packagist.org/packages/innmind/event-bus#4.0.0"];
    innmind__ip [shape="ellipse", width="0.75", height="0.5", color="#fc3785", URL="https://packagist.org/packages/innmind/ip#2.0.0"];
    innmind__http [shape="ellipse", width="0.75", height="0.5", color="#324178", URL="https://packagist.org/packages/innmind/http#4.2.0"];
    guzzlehttp__guzzle [shape="ellipse", width="0.75", height="0.5", color="#e5a30c", URL="https://packagist.org/packages/guzzlehttp/guzzle#6.5.2"];
    ramsey__uuid [shape="ellipse", width="0.75", height="0.5", color="#44619b", URL="https://packagist.org/packages/ramsey/uuid#3.9.3"];
    guzzlehttp__psr7 [shape="ellipse", width="0.75", height="0.5", color="#adcacd", URL="https://packagist.org/packages/guzzlehttp/psr7#1.6.1"];
    ralouphie__getallheaders [shape="ellipse", width="0.75", height="0.5", color="#373d23", URL="https://packagist.org/packages/ralouphie/getallheaders#3.0.3"];
    guzzlehttp__promises [shape="ellipse", width="0.75", height="0.5", color="#e89948", URL="https://packagist.org/packages/guzzlehttp/promises#v1.3.1"];
    paragonie__random_compat [shape="ellipse", width="0.75", height="0.5", color="#ea17ff", URL="https://packagist.org/packages/paragonie/random_compat#v9.99.99"];
    innmind__graphviz [shape="ellipse", width="0.75", height="0.5", color="#39df6f", URL="https://packagist.org/packages/innmind/graphviz#2.0.0"];
    innmind__colour [shape="ellipse", width="0.75", height="0.5", color="#356a4c", URL="https://packagist.org/packages/innmind/colour#3.1.0"];
}
DOT;

        $this->assertSame($expected, (new Render)(...unwrap($packages))->toString());
    }
}
