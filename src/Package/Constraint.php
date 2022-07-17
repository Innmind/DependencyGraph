<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\Exception\DomainException;
use Innmind\Immutable\{
    Str,
    Maybe,
};
use Composer\Semver\Semver;

/**
 * @psalm-immutable
 */
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

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function maybe(string $value): Maybe
    {
        return Maybe::just(Str::of($value))
            ->filter(static fn($value) => !$value->empty())
            ->map(static fn($value) => new self($value->toString()));
    }

    public function satisfiedBy(Version $version): bool
    {
        /** @psalm-suppress ImpureMethodCall */
        return Semver::satisfies($version->toString(), $this->value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
