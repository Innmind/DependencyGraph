<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Package;

final class Relation
{
    private $name;

    public function __construct(Name $name)
    {
        $this->name = $name;
    }

    public function name(): Name
    {
        return $this->name;
    }
}
