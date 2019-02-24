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
    public static function graph(Locate $locate, Package ...$packages): SetInterface
    {
        $packages = Set::of(Package::class, ...$packages)->reduce(
            Map::of('string', Package::class),
            static function(MapInterface $packages, Package $package): MapInterface {
                return $packages->put(
                    (string) $package->name(),
                    $package
                );
            }
        );
        $nodes = $packages->values()->reduce(
            Map::of('string', Node::class),
            static function(MapInterface $nodes, Package $package) use ($locate, $packages): MapInterface {
                $node = PackageNode::node($package, $nodes, $locate, $packages);

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

    private static function node(
        Package $package,
        MapInterface $nodes,
        Locate $locate,
        MapInterface $packages
    ): Node {
        $colour = self::colorize($package->name());
        $node = self::of($package->name())
            ->target($locate($package))
            ->shaped(Node\Shape::ellipse()->withColor($colour));

        return $package->relations()->reduce(
            $node,
            function(Node $package, Relation $relation) use ($nodes, $colour, $packages): Node {
                $node = self::of($relation->name());

                // if the package has already been transformed into a node, then
                // reuse its instance so the attributes are not lost
                $edge = $package
                    ->linkedTo($nodes[(string) $node->name()] ?? $node)
                    ->useColor($colour)
                    ->displayAs((string) $relation->constraint());
                $version = $packages->get((string) $relation->name())->version();

                if (!$relation->constraint()->satisfiedBy($version)) {
                    $edge->bold()->useColor(RGBA::fromString('FF0000'));
                }

                return $package;
            }
        );
    }

    private static function colorize(Name $name): RGBA
    {
        $hash = Str::of(\md5((string) $name));
        $red = $hash->substring(0, 2);
        $green = $hash->substring(2, 2);
        $blue = $hash->substring(4, 2);

        return RGBA::fromString($red.$green.$blue);
    }
}
