<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Package\Relation,
    Package\Name,
    Render\PackageNode,
    Render\Cluster,
};
use Innmind\Graphviz\{
    Graph,
    Graph\Rankdir,
    Node,
    Layout\Dot,
};
use Innmind\Stream\Readable;

final class Render
{
    public function __invoke(Package ...$packages): Readable
    {
        $graph = Graph\Graph::directed('packages', Rankdir::leftToRight());

        // create the dependencies between the packages
        $nodes = PackageNode::graph(...$packages);
        $graph = $nodes->reduce(
            $graph,
            function(Graph $graph, Node $node): Graph {
                return $graph->add($node);
            }
        );

        // cluster packages by vendor
        $graph = Vendor::group(...$packages)->reduce(
            $graph,
            function(Graph $graph, Vendor $vendor): Graph {
                return $graph->cluster(
                    Cluster::of($vendor)
                );
            }
        );

        // render
        return (new Dot)($graph);
    }
}
