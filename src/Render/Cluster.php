<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Render;

use Innmind\DependencyGraph\Package;
use Innmind\Graphviz\Graph;
use Innmind\Immutable\{
    Set,
    Str,
};

final class Cluster
{
    private function __construct()
    {
    }

    public static function of(string $name, Package ...$packages): Graph
    {
        return Set::of(Package::class, ...$packages)->reduce(
            Graph\Graph::directed((string) Str::of($name)->replace('-', '_'))
                ->displayAs($name),
            function(Graph $cluster, Package $package): Graph {
                return $cluster->add(
                    PackageNode::of($package->name())
                        ->displayAs($package->name()->package())
                );
            }
        );
    }
}
