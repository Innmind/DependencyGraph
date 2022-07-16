<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader\Dependents;

use Innmind\DependencyGraph\{
    Package,
    Package\Name,
};
use Innmind\Immutable\Set;

final class Graph
{
    private Package $package;
    /** @var Set<self> */
    private Set $parents;
    /** @var Set<self> */
    private Set $children;
    private bool $cleaned = false;

    private function __construct(Package $package)
    {
        $this->package = $package;
        /** @var Set<self> */
        $this->parents = Set::of();
        /** @var Set<self> */
        $this->children = Set::of();
    }

    /**
     * @no-named-arguments
     *
     * @return Set<Package>
     */
    public static function of(Package $root, Package ...$dependents): Set
    {
        $root = new self($root->removeRelations());
        $dependents = Set::of(...$dependents)->map(
            static fn(Package $package): self => new self($package),
        );
        self::bind($root, $dependents);
        $_ = $dependents->foreach(
            static fn(self $dependent) => self::bind($dependent, $dependents),
        );
        $root->keepPaths($root->package->name());

        return $root->collectPackages();
    }

    /**
     * Create biderectionnal relations between dependents and dependencies
     *
     * @param Set<self> $dependents
     */
    private static function bind(self $node, Set $dependents): void
    {
        $_ = $dependents->foreach(static function(self $dependent) use ($node): void {
            if ($dependent->package->dependsOn($node->package->name())) {
                $node->add($dependent);
            }
        });
    }

    private function add(self $parent): void
    {
        $this->parents = ($this->parents)($parent);
        $parent->children = ($parent->children)($this);
    }

    private function keepPaths(Name $root): void
    {
        if ($this->cleaned) {
            return;
        }

        $this->cleaned = true;

        $_ = $this->parents->foreach(static function(self $parent) use ($root): void {
            $parent->keepRelationsToChildren($root);
        });
        $_ = $this->parents->foreach(static function(self $parent) use ($root): void {
            $parent->keepPaths($root);
        });
    }

    private function keepRelationsToChildren(Name $root): void
    {
        $children = $this
            ->children
            ->filter(static fn($child) => $child->dependsOn($root))
            ->map(static fn(self $child): Name => $child->package->name());

        $this->package = $this->package->keep(($children)($root));
    }

    private function dependsOn(Name $root): bool
    {
        if ($this->package->dependsOn($root)) {
            return true;
        }

        return $this->children->any(
            static fn($child) => $child->dependsOn($root),
        );
    }

    /**
     * @return Set<Package>
     */
    private function collectPackages(): Set
    {
        return $this
            ->parents
            ->flatMap(static fn($parent) => $parent->collectPackages())
            ->add($this->package);
    }
}
