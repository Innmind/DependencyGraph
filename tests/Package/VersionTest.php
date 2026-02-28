<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\{
    Package\Version,
    Exception\DomainException,
};
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class VersionTest extends TestCase
{
    use BlackBox;

    public function testInterface(): BlackBox\Proof
    {
        return $this
            ->forAll(Set\Strings::any()->filter(static fn($string) => $string !== ''))
            ->prove(function(string $string): void {
                $this->assertSame($string, Version::of($string)->toString());
            });
    }

    public function testThrowWhenEmptyVersion()
    {
        $this->expectException(DomainException::class);

        Version::of('');
    }
}
