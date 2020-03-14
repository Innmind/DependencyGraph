<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\Exception\DomainException;
use Innmind\Immutable\Str;

final class Version
{
    private string $version;

    public function __construct(string $version)
    {
        if (Str::of($version)->empty()) {
            throw new DomainException;
        }

        $this->version = $version;
    }

    public function toString(): string
    {
        return $this->version;
    }
}
