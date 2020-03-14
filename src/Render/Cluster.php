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
        $name = $vendor->name()->toString();
        $cluster = Graph\Graph::directed(
            Str::of($name)->replace('-', '_')->toString()
        );
        $cluster->displayAs($name);
        $cluster->target($vendor->packagist());

        $vendor->packages()->foreach(
            static function(Package $package) use ($cluster): void {
                $node = PackageNode::of($package->name());
                $node->displayAs("{$package->name()->package()}@{$package->version()->toString()}");

                $cluster->add($node);
            },
        );

        return $cluster;
    }
}
