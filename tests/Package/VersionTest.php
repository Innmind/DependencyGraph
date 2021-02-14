<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\{
    Package\Version,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class VersionTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this
            ->forAll(Set\Strings::any()->filter(static fn($string) => $string !== ''))
            ->then(function(string $string): void {
                $this->assertSame($string, (new Version($string))->toString());
            });
    }

    public function testThrowWhenEmptyVersion()
    {
        $this->expectException(DomainException::class);

        new Version('');
    }
}
