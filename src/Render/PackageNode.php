<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Render;

use Innmind\DependencyGraph\{
    Package,
    Package\Relation,
    Package\Name,
};
use Innmind\Graphviz\{
    Node,
    Edge,
};
use Innmind\Colour\{
    Colour,
    RGBA,
};
use Innmind\Immutable\{
    Set,
    Str,
};

final class PackageNode
{
    private function __construct()
    {
    }

    /**
     * @psalm-pure
     *
     * @param Set<Package> $packages
     *
     * @return Set<Node>
     */
    public static function graph(Locate $locate, Set $packages): Set
    {
        return $packages->map(
            static fn($package) => self::node($package, $locate, $packages),
        );
    }

    /**
     * @psalm-pure
     */
    public static function of(Name $name): Node
    {
        $name = Str::of($name->toString())
            ->replace('-', '_')
            ->replace('.', '_')
            ->replace('/', '__')
            ->toString();

        return Node::named($name);
    }

    /**
     * @psalm-pure
     *
     * @param Set<Package> $packages
     */
    private static function node(
        Package $package,
        Locate $locate,
        Set $packages,
    ): Node {
        $colour = self::colorize($package->name());
        $node = self::of($package->name())
            ->target($locate($package))
            ->shaped(Node\Shape::ellipse()->withColor($colour));

        return $package->relations()->reduce(
            $node,
            static fn(Node $node, $relation) => $node->linkedTo(
                self::of($relation->name())->name(),
                static fn($edge) => $packages
                    ->find(static fn($package) => $package->name()->equals($relation->name()))
                    ->map(static fn($package) => self::edgeStyle(
                        $package,
                        $relation,
                        $edge->useColor($colour), // by default use the package colour
                    ))
                    ->match(
                        static fn($edge) => $edge,
                        static fn() => $edge->dotted()->bold()->useColor(Colour::red->toRGBA()),
                    ),
            ),
        );
    }

    /**
     * @psalm-pure
     */
    private static function colorize(Name $name): RGBA
    {
        $hash = Str::of(\md5($name->toString()));
        $red = $hash->take(2)->toString();
        $green = $hash->drop(2)->take(2)->toString();
        $blue = $hash->drop(4)->take(2)->toString();

        return RGBA::of($red.$green.$blue);
    }

    /**
     * @psalm-pure
     */
    private static function edgeStyle(
        Package $package,
        Relation $relation,
        Edge $edge,
    ): Edge {
        if ($package->abandoned()) {
            $edge = $edge->dotted();
        }

        if (!$relation->constraint()->satisfiedBy($package->version())) {
            $edge = $edge->bold()->useColor(Colour::red->toRGBA());
        }

        return $edge->displayAs($relation->constraint()->toString());
    }
}
