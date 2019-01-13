<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\{
    Vendor,
    Exception\DomainException,
};
use Innmind\Immutable\Str;

final class Name
{
    private $vendor;
    private $package;

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
        [$vendor, $package] = Str::of($name)->split('/');

        return new self(
            new Vendor\Name((string) $vendor),
            (string) $package
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
        return (string) $this === (string) $self;
    }

    public function __toString(): string
    {
        return $this->vendor.'/'.$this->package;
    }
}
