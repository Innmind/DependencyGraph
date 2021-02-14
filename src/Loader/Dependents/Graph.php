<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader\Dependents;

use Innmind\DependencyGraph\{
    Package,
    Package\Name,
};
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;

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
        $this->parents = Set::of(self::class);
        $this->children = Set::of(self::class);
    }

    /**
     * @return Set<Package>
     */
    public static function of(Package $root, Package ...$dependents): Set
    {
        $root = new self($root->removeRelations());
        $dependents = Set::of(Package::class, ...$dependents)->mapTo(
            self::class,
            static fn(Package $package): self => new self($package),
        );
        self::bind($root, $dependents);
        $dependents->foreach(
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
        $dependents->foreach(static function(self $dependent) use ($node): void {
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

        $this->parents->foreach(static function(self $parent) use ($root): void {
            $parent->keepRelationsToChildren($root);
        });
        $this->parents->foreach(static function(self $parent) use ($root): void {
            $parent->keepPaths($root);
        });
    }

    private function keepRelationsToChildren(Name $root): void
    {
        $children = $this
            ->children
            ->filter(static function(self $child) use ($root): bool {
                return $child->dependsOn($root);
            })
            ->mapTo(
                Name::class,
                static fn(self $child): Name => $child->package->name(),
            );
        $this->package = $this->package->keep($root, ...unwrap($children));
    }

    private function dependsOn(Name $root): bool
    {
        if ($this->package->dependsOn($root)) {
            return true;
        }

        return $this->children->reduce(
            false,
            static fn(bool $dependsOn, self $child): bool => $dependsOn || $child->dependsOn($root),
        );
    }

    /**
     * @return Set<Package>
     */
    private function collectPackages(): Set
    {
        /** @var Set<Package> */
        return $this->parents->reduce(
            Set::of(Package::class, $this->package),
            static fn(Set $packages, self $parent): Set => $packages->merge(
                $parent->collectPackages()
            ),
        );
    }
}
