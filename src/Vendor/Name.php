<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Vendor;

use Innmind\DependencyGraph\Exception\DomainException;
use Innmind\Immutable\Str;

final class Name
{
    private string $value;

    public function __construct(string $value)
    {
        if (Str::of($value)->empty()) {
            throw new DomainException;
        }

        $this->value = $value;
    }

    public function equals(self $self): bool
    {
        return $this->toString() === $self->toString();
    }

    public function toString(): string
    {
        return $this->value;
    }
}
