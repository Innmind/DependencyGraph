<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\{
    Package\Version,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait,
};

class VersionTest extends TestCase
{
    use TestTrait;

    public function testInterface()
    {
        $this
            ->forAll(Generator\string())
            ->when(static function(string $string): bool {
                return $string !== '';
            })
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
