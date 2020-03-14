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
use Innmind\Url\{
    Url,
    Fragment,
};
use Innmind\Stream\Readable;

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

    public function __invoke(Package ...$packages): Readable
    {
        $graph = Graph\Graph::directed('packages');

        // create the dependencies between the packages
        $nodes = PackageNode::graph($this->locate, ...$packages);
        $nodes->foreach(
            static fn(Node $node) => $graph->add($node),
        );

        // cluster packages by vendor
        Vendor::group(...$packages)->foreach(
            static fn(Vendor $vendor) => $graph->cluster(
                Cluster::of($vendor),
            ),
        );

        // render
        return (new Dot)($graph);
    }
}
