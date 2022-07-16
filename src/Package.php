<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Package\Name,
    Package\Version,
    Package\Relation,
    Vendor,
};
use Innmind\Url\Url;
use Innmind\Immutable\Set;

final class Package
{
    private Name $name;
    private Version $version;
    private Url $packagist;
    /** @var Set<Relation> */
    private Set $relations;

    /**
     * @no-named-arguments
     */
    public function __construct(
        Name $name,
        Version $version,
        Url $packagist,
        Relation ...$relations,
    ) {
        $this->name = $name;
        $this->version = $version;
        $this->packagist = $packagist;
        $this->relations = Set::of(...$relations);
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function version(): Version
    {
        return $this->version;
    }

    public function packagist(): Url
    {
        return $this->packagist;
    }

    /**
     * @return Set<Relation>
     */
    public function relations(): Set
    {
        return $this->relations;
    }

    public function dependsOn(Name $name): bool
    {
        return $this->relations->any(
            static fn($relation) => $relation->name()->equals($name),
        );
    }

    /**
     * Remove all the relations not from the given set
     *
     * @no-named-arguments
     */
    public function keep(Name ...$packages): self
    {
        $packages = Set::of(...$packages);

        $self = clone $this;
        $self->relations = $this->relations->filter(static function(Relation $relation) use ($packages): bool {
            return $packages->any(
                static fn($package) => $relation->name()->equals($package),
            );
        });

        return $self;
    }

    public function removeRelations(): self
    {
        $self = clone $this;
        $self->relations = $this->relations->clear();

        return $self;
    }
}
