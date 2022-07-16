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

final class PackageNode
{
    private function __construct()
    {
    }

    /**
     * @no-named-arguments
     *
     * @return Set<Node>
     */
    public static function graph(Locate $locate, Package ...$packages): Set
    {
        $packages = Set::of(...$packages)
            ->groupBy(static fn($package) => $package->name()->toString())
            ->map(static fn($_, $packages) => $packages->find(static fn() => true)->match(
                static fn($package) => $package,
                static fn() => throw new \LogicException('unreachable'),
            ));
        /** @var Map<string, Node> */
        $nodes = $packages->values()->reduce(
            Map::of(),
            static function(Map $nodes, Package $package) use ($locate, $packages): Map {
                /** @var Map<string, Node> $nodes */
                $node = self::node($package, $nodes, $locate, $packages);

                return ($nodes)($node->name()->toString(), $node);
            },
        );

        return Set::of(...$nodes->values()->toList());
    }

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
     * @param Map<string, Node> $nodes
     * @param Map<string, Package> $packages
     */
    private static function node(
        Package $package,
        Map $nodes,
        Locate $locate,
        Map $packages,
    ): Node {
        $colour = self::colorize($package->name());
        $node = self::of($package->name())
            ->target($locate($package))
            ->shaped(Node\Shape::ellipse()->withColor($colour));

        return $package->relations()->reduce(
            $node,
            static function(Node $package, Relation $relation) use ($nodes, $colour, $packages): Node {
                $node = self::of($relation->name());
                $upToDate = $packages
                    ->get($relation->name()->toString())
                    ->map(static fn($package) => $package->version())
                    ->filter(static fn($version) => $relation->constraint()->satisfiedBy($version))
                    ->match(
                        static fn() => true,
                        static fn() => false,
                    );

                // if the package has already been transformed into a node, then
                // reuse its instance so the attributes are not lost
                return $package->linkedTo(
                    $nodes->get($node->name()->toString())->match(
                        static fn($node) => $node->name(),
                        static fn() => $node->name(),
                    ),
                    static fn($edge) => (match ($upToDate) {
                        false => $edge->bold()->useColor(RGBA::of('FF0000')),
                        true => $edge->useColor($colour),
                    })
                        ->displayAs($relation->constraint()->toString()),
                );
            },
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
