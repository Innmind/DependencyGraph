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
use Innmind\Immutable\Set;

final class Render
{
    private Locate $locate;

    public function __construct(Locate $locate = null)
    {
        $this->locate = $locate ?? new class implements Locate {
            /**
             * @psalm-pure
             */
            public function __invoke(Package $package): Url
            {
                return $package->packagist()->withFragment(Fragment::of(
                    $package->version()->toString(),
                ));
            }
        };
    }

    /**
     * @param Set<Package> $packages
     */
    public function __invoke(Set $packages): Content
    {
        $graph = Graph::directed('packages');

        // create the dependencies between the packages
        $nodes = PackageNode::graph($this->locate, $packages);
        /** @var Graph<'directed'> */
        $graph = $nodes->reduce(
            $graph,
            static fn(Graph $graph, Node $node) => $graph->add($node),
        );

        // cluster packages by vendor
        /** @var Graph<'directed'> */
        $graph = Vendor::group($packages)->reduce(
            $graph,
            static fn(Graph $graph, Vendor $vendor) => $graph->cluster(
                Cluster::of($vendor),
            ),
        );

        // render
        return Dot::of()($graph);
    }
}
