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

final class Name
{
    private Vendor\Name $vendor;
    private string $package;

    private function __construct(Vendor\Name $vendor, string $package)
    {
        if (Str::of($package)->empty()) {
            throw new DomainException;
        }

        $this->vendor = $vendor;
        $this->package = $package;
    }

    public static function of(string $name): self
    {
        [$vendor, $package] = Str::of($name)->split('/')->toList();

        return new self(
            Vendor\Name::of($vendor->toString()),
            $package->toString(),
        );
    }

    /**
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

    public function package(): string
    {
        return $this->package;
    }

    public function equals(self $self): bool
    {
        return $this->toString() === $self->toString();
    }

    public function toString(): string
    {
        return $this->vendor->toString().'/'.$this->package;
    }
}
