<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\{
    Package\Constraint,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;
use Eris\{
    Generator,
    TestTrait,
};

class ConstraintTest extends TestCase
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
                $this->assertSame($string, (string) new Constraint($string));
            });
    }

    public function testThrowWhenEmptyConstraint()
    {
        $this->expectException(DomainException::class);

        new Constraint('');
    }
}
