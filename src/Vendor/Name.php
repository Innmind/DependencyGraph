<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Vendor;

use Innmind\DependencyGraph\Exception\DomainException;
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Name
{
    private string $value;

    private function __construct(string $value)
    {
        if (Str::of($value)->empty()) {
            throw new DomainException;
        }

        $this->value = $value;
    }

    /**
     * @psalm-pure
     */
    public static function of(string $value): self
    {
        return new self($value);
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

    public function equals(self $self): bool
    {
        return $this->toString() === $self->toString();
    }

    public function toString(): string
    {
        return $this->value;
    }
}
