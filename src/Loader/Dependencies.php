<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Dependents\Graph,
    Package as Model,
};
use Innmind\Immutable\{
    Set,
    Map,
    Maybe,
};

final class Dependencies
{
    private Package $load;
    /** @var Map<string, \WeakReference<Model>> */
    private Map $cache;

    public function __construct(Package $load)
    {
        $this->load = $load;
        /** @var Map<string, \WeakReference<Model>> */
        $this->cache = Map::of();
    }

    /**
     * @return Set<Model>
     */
    public function __invoke(Model\Name $name): Set
    {
        return ($this->load)($name)
            ->map($this->cache(...))
            ->match(
                $this->loadRelations(...),
                static fn() => Set::of(),
            );
    }

    /**
     * @return Set<Model>
     */
    private function loadRelations(Model $dependency): Set
    {
        return $dependency
            ->relations()
            ->map(static fn($relation) => $relation->name())
            ->map($this->lookup(...))
            ->flatMap(
                static fn($packages) => $packages
                    ->toSequence()
                    ->toSet()
                    ->flatMap(static fn($packages) => $packages),
            )
            ->add($dependency);
    }

    /**
     * @return Maybe<Set<Model>>
     */
    private function lookup(Model\Name $relation): Maybe
    {
        /** @psalm-suppress InvalidArgument Because it doesn't understand the filter */
        return $this
            ->cache
            ->get($relation->toString())
            ->filter(static fn($ref) => \is_object($ref->get()))
            ->map(static fn($ref) => $ref->get())
            ->otherwise(fn() => $this->fetch($relation))
            ->map($this->loadRelations(...));
    }

    /**
     * @return Maybe<Model>
     */
    private function fetch(Model\Name $relation): Maybe
    {
        // remove dead references
        $this->cache = $this->cache->filter(
            static fn($_, $ref) => \is_object($ref->get()),
        );

        return ($this->load)($relation)->map($this->cache(...));
    }

    private function cache(Model $package): Model
    {
        $this->cache = ($this->cache)(
            $package->name()->toString(),
            \WeakReference::create($package),
        );

        return $package;
    }
}
