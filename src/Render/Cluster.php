<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Render;

use Innmind\DependencyGraph\{
    Package,
    Vendor,
};
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

    public static function of(Vendor $vendor): Graph
    {
        $name = (string) $vendor->name();

        return Set::of(Package::class, ...$vendor)->reduce(
            Graph\Graph::directed((string) Str::of($name)->replace('-', '_'))
                ->displayAs($name)
                ->target($vendor->packagist()),
            function(Graph $cluster, Package $package): Graph {
                return $cluster->add(
                    PackageNode::of($package->name())
                        ->displayAs($package->name()->package())
                );
            }
        );
    }
}
