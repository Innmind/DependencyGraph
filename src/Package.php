<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\DependencyGraph\{
    Package\Name,
    Package\Relation,
    Vendor,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class Package
{
    private $name;
    private $packagist;
    private $relations;

    public function __construct(
        Name $name,
        UrlInterface $packagist,
        Relation ...$relations
    ) {
        $this->name = $name;
        $this->packagist = $packagist;
        $this->relations = Set::of(Relation::class, ...$relations);
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function packagist(): UrlInterface
    {
        return $this->packagist;
    }

    /**
     * @return SetInterface<Relation>
     */
    public function relations(): SetInterface
    {
        return $this->relations;
    }

    public function dependsOn(Name $name): bool
    {
        return $this->relations->reduce(
            false,
            static function(bool $dependsOn, Relation $relation) use ($name): bool {
                return $dependsOn || $relation->name()->equals($name);
            }
        );
    }

    /**
     * Remove all the relations not from the given set
     */
    public function keep(Name ...$packages): self
    {
        $packages = Set::of(Name::class, ...$packages);

        $self = clone $this;
        $self->relations = $this->relations->filter(static function(Relation $relation) use ($packages): bool {
            return $packages->reduce(
                false,
                static function(bool $inSet, Name $package) use ($relation): bool {
                    return $inSet || $relation->name()->equals($package);
                }
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
