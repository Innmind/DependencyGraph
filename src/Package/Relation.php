<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Package;

final class Relation
{
    private Name $name;
    private Constraint $constraint;

    public function __construct(Name $name, Constraint $constraint)
    {
        $this->name = $name;
        $this->constraint = $constraint;
    }

    public static function of(Name $name, Constraint $constraint): self
    {
        return new self($name, $constraint);
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
