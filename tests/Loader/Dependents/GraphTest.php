<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader\Dependents;

use Innmind\DependencyGraph\{
    Loader\Dependents\Graph,
    Package,
    Package\Name,
    Package\Version,
    Package\Relation,
    Render,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\SetInterface;
use PHPUnit\Framework\TestCase;

class GraphTest extends TestCase
{
    public function testKeepPathsLeadingToTheRootPackage()
    {
        $packages = Graph::of(
            new Package(
                Name::of('vendor/root'),
                new Version('1.0.0'),
                $this->createMock(UrlInterface::class),
                new Relation(
                    Name::of('rand/om')
                )
            ),
            new Package(
                Name::of('vendor/libA'),
                new Version('1.0.0'),
                $this->createMock(UrlInterface::class),
                new Relation(
                    Name::of('vendor/root')
                ),
                new Relation(
                    Name::of('watev/lib')
                )
            ),
            new Package(
                Name::of('vendor/libB'),
                new Version('1.0.0'),
                $this->createMock(UrlInterface::class),
                new Relation(
                    Name::of('vendor/root')
                ),
                new Relation(
                    Name::of('watev/other')
                )
            ),
            new Package(
                Name::of('watev/foo'),
                new Version('1.0.0'),
                $this->createMock(UrlInterface::class),
                new Relation(
                    Name::of('vendor/libA')
                ),
                new Relation(
                    Name::of('vendor/libB')
                ),
                new Relation(
                    Name::of('vendor/libC')
                )
            ),
            new Package(
                Name::of('vendor/libC'),
                new Version('1.0.0'),
                $this->createMock(UrlInterface::class)
            )
        );

        $this->assertInstanceOf(SetInterface::class, $packages);
        $this->assertSame(Package::class, (string) $packages->type());
        $this->assertCount(4, $packages);

        $expected = <<<DOT
digraph packages {
    rankdir="LR";
    subgraph cluster_vendor {
        label="vendor"
        URL="https://packagist.org/packages/vendor/"
    vendor__root [label="root"];
    vendor__libA [label="libA"];
    vendor__libB [label="libB"];
    }
    subgraph cluster_watev {
        label="watev"
        URL="https://packagist.org/packages/watev/"
    watev__foo [label="foo"];
    }
    vendor__libA -> vendor__root [color="#c34ca0"];
    watev__foo -> vendor__libA [color="#416be8"];
    watev__foo -> vendor__libB [color="#416be8"];
    vendor__libB -> vendor__root [color="#f76ead"];
    vendor__root [shape="ellipse", width="0.75", height="0.5", color="#39b791", URL=""];
    vendor__libA [shape="ellipse", width="0.75", height="0.5", color="#c34ca0", URL=""];
    watev__foo [shape="ellipse", width="0.75", height="0.5", color="#416be8", URL=""];
    vendor__libB [shape="ellipse", width="0.75", height="0.5", color="#f76ead", URL=""];
}
DOT;

        $this->assertSame($expected, (string) (new Render)(...$packages));
    }
}
