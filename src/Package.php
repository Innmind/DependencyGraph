<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Package\Name,
    Package\Version,
    Package\Relation,
};
use Innmind\Url\Url;
use Innmind\Immutable\Set;

/**
 * @psalm-immutable
 */
final class Package
{
    private Name $name;
    private Version $version;
    private Url $packagist;
    /** @var Set<Relation> */
    private Set $relations;
    private bool $abandoned;

    /**
     * @param Set<Relation> $relations
     */
    public function __construct(
        Name $name,
        Version $version,
        Url $packagist,
        Set $relations,
        bool $abandoned = false,
    ) {
        $this->name = $name;
        $this->version = $version;
        $this->packagist = $packagist;
        $this->relations = $relations;
        $this->abandoned = $abandoned;
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

    public function abandoned(): bool
    {
        return $this->abandoned;
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
     * @param Set<Name> $packages
     */
    public function keep(Set $packages): self
    {
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
