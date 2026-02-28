<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\{
    Vendor,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Name
{
    private Vendor\Name $vendor;
    /** @var non-empty-string */
    private string $package;

    private function __construct(Vendor\Name $vendor, string $package)
    {
        if (Str::of($package)->empty()) {
            throw new DomainException;
        }

        $this->vendor = $vendor;
        /** @var non-empty-string */
        $this->package = $package;
    }

    /**
     * @psalm-pure
     */
    public static function of(string $name): self
    {
        /** @psalm-suppress PossiblyUndefinedArrayOffset */
        [$vendor, $package] = Str::of($name)->split('/')->toList();

        return new self(
            Vendor\Name::of($vendor->toString()),
            $package->toString(),
        );
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function maybe(string $name): Maybe
    {
        $parts = Str::of($name)->split('/');
        $vendor = $parts
            ->first()
            ->map(static fn($value) => $value->toString())
            ->flatMap(Vendor\Name::maybe(...));
        $package = $parts
            ->get(1)
            ->filter(static fn($value) => !$value->empty())
            ->map(static fn($value) => $value->toString());

        return Maybe::all($vendor, $package)->map(
            static fn(Vendor\Name $vendor, string $package) => new self($vendor, $package),
        );
    }

    public function vendor(): Vendor\Name
    {
        return $this->vendor;
    }

    /**
     * @return non-empty-string
     */
    public function package(): string
    {
        return $this->package;
    }

    public function equals(self $self): bool
    {
        return $this->toString() === $self->toString();
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->vendor->toString().'/'.$this->package;
    }
}
