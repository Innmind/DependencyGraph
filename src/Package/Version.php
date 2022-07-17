<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\Exception\DomainException;
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Version
{
    private string $version;

    private function __construct(string $version)
    {
        if (Str::of($version)->empty()) {
            throw new DomainException;
        }

        $this->version = $version;
    }

    /**
     * @psalm-pure
     */
    public static function of(string $version): self
    {
        return new self($version);
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function maybe(string $version): Maybe
    {
        return Maybe::just(Str::of($version))
            ->filter(static fn($version) => !$version->empty())
            ->map(static fn($version) => new self($version->toString()));
    }

    public function toString(): string
    {
        return $this->version;
    }
}
