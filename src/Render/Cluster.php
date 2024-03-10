<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Render;

use Innmind\DependencyGraph\Vendor;
use Innmind\Graphviz\Graph;
use Innmind\Immutable\Str;

final class Cluster
{
    private function __construct()
    {
    }

    /**
     * @psalm-pure
     *
     * @return Graph<'directed'>
     */
    public static function of(Vendor $vendor): Graph
    {
        $name = $vendor->name()->toString();
        $cluster = Graph::directed(
            Str::of($name)->replace('-', '_')->toString(),
        )
            ->displayAs($name)
            ->target($vendor->packagist());

        /** @var Graph<'directed'> */
        return $vendor
            ->packages()
            ->map(
                static fn($package) => PackageNode::of($package->name())->displayAs(
                    "{$package->name()->package()}@{$package->version()->toString()}",
                ),
            )
            ->reduce(
                $cluster,
                static fn(Graph $cluster, $node) => $cluster->add($node),
            );
    }
}
