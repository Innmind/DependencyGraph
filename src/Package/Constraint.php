<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\Exception\DomainException;
use Innmind\Immutable\Str;
use Composer\Semver\Semver;

final class Constraint
{
    private string $value;

    public function __construct(string $value)
    {
        if (Str::of($value)->empty()) {
            throw new DomainException;
        }

        $this->value = $value;
    }

    public function satisfiedBy(Version $version): bool
    {
        return Semver::satisfies((string) $version, $this->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
