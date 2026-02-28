<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\{
    Package\Constraint,
    Package\Version,
    Exception\DomainException,
};
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class ConstraintTest extends TestCase
{
    use BlackBox;

    public function testInterface(): BlackBox\Proof
    {
        return $this
            ->forAll(Set\Strings::any()->filter(static fn($string) => $string !== ''))
            ->prove(function(string $string): void {
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

        $this->assertTrue($constraint->satisfiedBy(Version::of('1.1')));
        $this->assertTrue($constraint->satisfiedBy(Version::of('2.0')));
        $this->assertFalse($constraint->satisfiedBy(Version::of('2.1')));
        $this->assertFalse($constraint->satisfiedBy(Version::of('1.0')));
    }
}
