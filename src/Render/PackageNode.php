<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Render;

use Innmind\DependencyGraph\{
    Package,
    Package\Relation,
    Package\Name,
};
use Innmind\Graphviz\Node;
use Innmind\Colour\RGBA;
use Innmind\Immutable\{
    Map,
    Set,
    Str,
};
use function Innmind\Immutable\unwrap;

final class PackageNode
{
    private function __construct()
    {
    }

    /**
     * @return Set<Node>
     */
    public static function graph(Locate $locate, Package ...$packages): Set
    {
        $packages = Set::of(Package::class, ...$packages)->reduce(
            Map::of('string', Package::class),
            static function(Map $packages, Package $package): Map {
                return $packages->put(
                    $package->name()->toString(),
                    $package
                );
            }
        );
        $nodes = $packages->values()->reduce(
            Map::of('string', Node::class),
            static function(Map $nodes, Package $package) use ($locate, $packages): Map {
                $node = PackageNode::node($package, $nodes, $locate, $packages);

                return $nodes->put($node->name()->toString(), $node);
            }
        );

        return Set::of(Node::class, ...unwrap($nodes->values()));
    }

    public static function of(Name $name): Node\Node
    {
        $name = Str::of($name->toString())
            ->replace('-', '_')
            ->replace('.', '_')
            ->replace('/', '__')
            ->toString();

        return Node\Node::named($name);
    }

    private static function node(
        Package $package,
        Map $nodes,
        Locate $locate,
        Map $packages
    ): Node {
        $colour = self::colorize($package->name());
        $node = self::of($package->name());
        $node->target($locate($package));
        $node->shaped(Node\Shape::ellipse()->withColor($colour));

        return $package->relations()->reduce(
            $node,
            function(Node $package, Relation $relation) use ($nodes, $colour, $packages): Node {
                $node = self::of($relation->name());

                // if the package has already been transformed into a node, then
                // reuse its instance so the attributes are not lost
                $edge = $package->linkedTo(
                    $nodes->contains($node->name()->toString()) ?
                        $nodes->get($node->name()->toString()) : $node,
                );
                $edge->useColor($colour);
                $edge->displayAs($relation->constraint()->toString());
                $version = $packages->get($relation->name()->toString())->version();

                if (!$relation->constraint()->satisfiedBy($version)) {
                    $edge->bold();
                    $edge->useColor(RGBA::of('FF0000'));
                }

                return $package;
            }
        );
    }

    private static function colorize(Name $name): RGBA
    {
        $hash = Str::of(\md5($name->toString()));
        $red = $hash->substring(0, 2)->toString();
        $green = $hash->substring(2, 2)->toString();
        $blue = $hash->substring(4, 2)->toString();

        return RGBA::of($red.$green.$blue);
    }
}
