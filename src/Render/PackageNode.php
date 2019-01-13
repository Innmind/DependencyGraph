<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Render;

use Innmind\DependencyGraph\{
    Package,
    Package\Relation,
    Package\Name,
};
use Innmind\Graphviz\Node;
use Innmind\Immutable\{
    MapInterface,
    Map,
    SetInterface,
    Set,
    Str,
};

final class PackageNode
{
    private function __construct()
    {
    }

    /**
     * @return SetInterface<Node>
     */
    public static function graph(Package ...$packages): SetInterface
    {
        $nodes = Set::of(Package::class, ...$packages)->reduce(
            Map::of('string', Node::class),
            static function(MapInterface $nodes, Package $package): MapInterface {
                $node = PackageNode::node($package, $nodes);

                return $nodes->put((string) $node->name(), $node);
            }
        );

        return Set::of(Node::class, ...$nodes->values());
    }

    public static function of(Name $name): Node\Node
    {
        $name = (string) Str::of((string) $name)
            ->replace('-', '_')
            ->replace('/', '__');

        return Node\Node::named($name);
    }

    private static function node(Package $package, MapInterface $nodes): Node
    {
        $node = self::of($package->name())
            ->target($package->packagist());

        return $package->relations()->reduce(
            $node,
            function(Node $node, Relation $relation) use ($nodes): Node {
                $relation = self::of($relation->name());

                // if the package has already been transformed into a node, then
                // reuse its instance so the attributes are not lost
                $node->linkedTo($nodes[(string) $relation->name()] ?? $relation);

                return $node;
            }
        );
    }
}
