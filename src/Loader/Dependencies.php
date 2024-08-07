<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\Package as Model;
use Innmind\Immutable\{
    Set,
    Map,
    Maybe,
    Predicate\Instance,
};

final class Dependencies
{
    private Package $load;
    /** @var Map<string, \WeakReference<Model>> */
    private Map $cache;
    /** @var Set<string> */
    private Set $loading;

    public function __construct(Package $load)
    {
        $this->load = $load;
        /** @var Map<string, \WeakReference<Model>> */
        $this->cache = Map::of();
        /** @var Set<string> */
        $this->loading = Set::of();
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
        if ($this->loading->contains($dependency->name()->toString())) {
            return Set::of($dependency);
        }

        $this->loading = ($this->loading)($dependency->name()->toString());
        $packages = $dependency
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
        $this->loading = $this->loading->remove($dependency->name()->toString());

        return $packages;
    }

    /**
     * @return Maybe<Set<Model>>
     */
    private function lookup(Model\Name $relation): Maybe
    {
        return Maybe::defer(fn() => $this->cache->get($relation->toString()))
            ->map(static fn($ref) => $ref->get())
            ->keep(Instance::of(Model::class))
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
