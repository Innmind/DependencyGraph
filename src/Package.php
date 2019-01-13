<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\DependencyGraph\Package\{
    Name,
    Relation,
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
    private $repository;
    private $relations;

    public function __construct(
        Name $name,
        UrlInterface $packagist,
        UrlInterface $repository,
        Relation ...$relations
    ) {
        $this->name = $name;
        $this->packagist = $packagist;
        $this->repository = $repository;
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

    public function repository(): UrlInterface
    {
        return $this->repository;
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
}
