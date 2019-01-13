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
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface,
    Set,
    Str,
};

final class Render
{
    public function __invoke(Package ...$packages): Readable
    {
        $packages = Set::of(Package::class, ...$packages);
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
        $graph = $packages
            ->groupBy(static function(Package $package): string {
                return $package->name()->vendor();
            })
            ->reduce(
                $graph,
                function(Graph $graph, string $name, SetInterface $vendors): Graph {
                    return $graph->cluster(
                        Cluster::of($name, ...$vendors)
                    );
                }
            );

        // render
        return (new Dot)($graph);
    }
}
