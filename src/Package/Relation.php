<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Package;

final class Relation
{
    private $name;
    private $constraint;

    public function __construct(Name $name, Constraint $constraint)
    {
        $this->name = $name;
        $this->constraint = $constraint;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function constraint(): Constraint
    {
        return $this->constraint;
    }
}
