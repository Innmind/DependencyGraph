<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader\Dependents;

use Innmind\DependencyGraph\{
    Loader\Dependents\Graph,
    Package,
    Package\Name,
    Package\Version,
    Package\Relation,
    Package\Constraint,
    Render,
};
use Innmind\Url\Url;
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class GraphTest extends TestCase
{
    public function testKeepPathsLeadingToTheRootPackage()
    {
        $packages = Graph::of(
            new Package(
                Name::of('vendor/root'),
                Version::of('1.0.0'),
                Url::of('http://example.com'),
                Url::of('http://example.com'),
                Set::of(new Relation(
                    Name::of('rand/om'),
                    new Constraint('~1.0'),
                )),
            ),
            Set::of(
                new Package(
                    Name::of('vendor/libA'),
                    Version::of('1.0.0'),
                    Url::of('http://example.com'),
                    Url::of('http://example.com'),
                    Set::of(
                        new Relation(
                            Name::of('vendor/root'),
                            new Constraint('~1.0'),
                        ),
                        new Relation(
                            Name::of('watev/lib'),
                            new Constraint('~1.0'),
                        ),
                    ),
                ),
                new Package(
                    Name::of('vendor/libB'),
                    Version::of('1.0.0'),
                    Url::of('http://example.com'),
                    Url::of('http://example.com'),
                    Set::of(
                        new Relation(
                            Name::of('vendor/root'),
                            new Constraint('~1.0'),
                        ),
                        new Relation(
                            Name::of('watev/other'),
                            new Constraint('~1.0'),
                        ),
                    ),
                ),
                new Package(
                    Name::of('watev/foo'),
                    Version::of('1.0.0'),
                    Url::of('http://example.com'),
                    Url::of('http://example.com'),
                    Set::of(
                        new Relation(
                            Name::of('vendor/libA'),
                            new Constraint('~1.0'),
                        ),
                        new Relation(
                            Name::of('vendor/libB'),
                            new Constraint('~1.0'),
                        ),
                        new Relation(
                            Name::of('vendor/libC'),
                            new Constraint('~1.0'),
                        ),
                    ),
                ),
                new Package(
                    Name::of('vendor/libC'),
                    Version::of('1.0.0'),
                    Url::of('http://example.com'),
                    Url::of('http://example.com'),
                    Set::of(),
                ),
            ),
        );

        $this->assertInstanceOf(Set::class, $packages);
        $this->assertCount(4, $packages);

        $expected = <<<DOT
digraph packages {
    subgraph cluster_vendor {
        label="vendor"
        URL="https://packagist.org/packages/vendor/"
    vendor__libA [label="libA@1.0.0"];
    vendor__libB [label="libB@1.0.0"];
    vendor__root [label="root@1.0.0"];
    }
    subgraph cluster_watev {
        label="watev"
        URL="https://packagist.org/packages/watev/"
    watev__foo [label="foo@1.0.0"];
    }
    vendor__libA -> vendor__root [color="#c34ca0", label="~1.0"];
    vendor__libB -> vendor__root [color="#f76ead", label="~1.0"];
    watev__foo -> vendor__libA [color="#416be8", label="~1.0"];
    watev__foo -> vendor__libB [color="#416be8", label="~1.0"];
    vendor__libA [shape="ellipse", width="0.75", height="0.5", color="#c34ca0", URL="http://example.com#1.0.0"];
    vendor__libB [shape="ellipse", width="0.75", height="0.5", color="#f76ead", URL="http://example.com#1.0.0"];
    watev__foo [shape="ellipse", width="0.75", height="0.5", color="#416be8", URL="http://example.com#1.0.0"];
    vendor__root [shape="ellipse", width="0.75", height="0.5", color="#39b791", URL="http://example.com#1.0.0"];
}
DOT;

        $this->assertSame($expected, (new Render)($packages)->toString());
    }
}
