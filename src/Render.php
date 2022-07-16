<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Package\Relation,
    Package\Name,
    Render\PackageNode,
    Render\Cluster,
    Render\Locate,
};
use Innmind\Graphviz\{
    Graph,
    Graph\Rankdir,
    Node,
    Layout\Dot,
};
use Innmind\Filesystem\File\Content;
use Innmind\Url\{
    Url,
    Fragment,
};

final class Render
{
    private Locate $locate;

    public function __construct(Locate $locate = null)
    {
        $this->locate = $locate ?? new class implements Locate {
            public function __invoke(Package $package): Url
            {
                return $package->packagist()->withFragment(Fragment::of(
                    $package->version()->toString(),
                ));
            }
        };
    }

    /**
     * @no-named-arguments
     */
    public function __invoke(Package ...$packages): Content
    {
        $graph = Graph::directed('packages');

        // create the dependencies between the packages
        $nodes = PackageNode::graph($this->locate, ...$packages);
        $graph = $nodes->reduce(
            $graph,
            static fn($graph, Node $node) => $graph->add($node),
        );

        // cluster packages by vendor
        $graph = Vendor::group(...$packages)->reduce(
            $graph,
            static fn($graph, Vendor $vendor) => $graph->cluster(
                Cluster::of($vendor),
            ),
        );

        // render
        return Dot::of()($graph);
    }
}
