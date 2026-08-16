<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Dependencies,
    Loader\Package,
    Package as PackageModel,
    Render,
};
use Innmind\HttpTransport\Transport;
use Innmind\Time\Clock;
use Innmind\Immutable\Set;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class DependenciesTest extends TestCase
{
    public function testInvokation()
    {
        $load = new Dependencies(
            new Package(
                Transport::curl(Clock::live())->map(
                    static fn($config) => $config->limitConcurrencyTo(20),
                ),
            ),
        );

        $packages = $load(PackageModel\Name::of('innmind/url'));

        $this->assertInstanceOf(Set::class, $packages);
        $this->assertSame(2, $packages->size());
        $expected = <<<DOT
digraph packages {
    subgraph cluster_innmind {
        label="innmind"
        URL="https://packagist.org/packages/innmind/"
    innmind__immutable [label="immutable@6.3.0"];
    innmind__url [label="url@5.4.0"];
    }
    innmind__url -> innmind__immutable [color="#085cd3", label="~6.0"];
    innmind__immutable [shape="ellipse", width="0.75", height="0.5", color="#a7e599", URL="https://packagist.org/packages/innmind/immutable#6.3.0"];
    innmind__url [shape="ellipse", width="0.75", height="0.5", color="#085cd3", URL="https://packagist.org/packages/innmind/url#5.4.0"];
}
DOT;

        $this->assertSame($expected, (new Render)($packages)->toString());
    }

    public function testDuplicatedRelationsRegression()
    {
        $load = new Dependencies(
            new Package(
                Transport::curl(Clock::live())->map(
                    static fn($config) => $config->limitConcurrencyTo(20),
                ),
            ),
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
                'innmind/mutable',
                'innmind/time',
                'innmind/url',
                'innmind/validation',
                'psr/log',
                'ramsey/collection',
                'ramsey/uuid',
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
            new Package(
                Transport::curl(Clock::live())->map(
                    static fn($config) => $config->limitConcurrencyTo(20),
                ),
            ),
        );

        $packages = $load(PackageModel\Name::of('laravel/browser-kit-testing'));

        $this->assertInstanceOf(Set::class, $packages);
        $this->assertSame(104, $packages->size());
    }
}
