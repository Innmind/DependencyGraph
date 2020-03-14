<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\{
    Vendor,
    Exception\DomainException,
};
use Innmind\Immutable\Str;
use function Innmind\Immutable\unwrap;

final class Name
{
    private Vendor\Name $vendor;
    private string $package;

    public function __construct(Vendor\Name $vendor, string $package)
    {
        if (Str::of($package)->empty()) {
            throw new DomainException;
        }

        $this->vendor = $vendor;
        $this->package = $package;
    }

    public static function of(string $name): self
    {
        [$vendor, $package] = unwrap(Str::of($name)->split('/'));

        return new self(
            new Vendor\Name($vendor->toString()),
            $package->toString()
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
