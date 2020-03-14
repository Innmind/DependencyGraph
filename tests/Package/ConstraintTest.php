<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\{
    Package\Constraint,
    Package\Version,
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
                $this->assertSame($string, (new Constraint($string))->toString());
            });
    }

    public function testThrowWhenEmptyConstraint()
    {
        $this->expectException(DomainException::class);

        new Constraint('');
    }

    public function testSatisfiedBy()
    {
        $constraint = new Constraint('>1.0 <2.1');

        $this->assertTrue($constraint->satisfiedBy(new Version('1.1')));
        $this->assertTrue($constraint->satisfiedBy(new Version('2.0')));
        $this->assertFalse($constraint->satisfiedBy(new Version('2.1')));
        $this->assertFalse($constraint->satisfiedBy(new Version('1.0')));
    }
}
