<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Package;

use Innmind\DependencyGraph\{
    Package\Relation,
    Package\Constraint,
    Package\Name,
    Vendor,
};
use PHPUnit\Framework\TestCase;

class RelationTest extends TestCase
{
    public function testInterface()
    {
        $relation = new Relation(
            $name = Name::of('foo/bar'),
            $constraint = new Constraint('~1.0'),
        );

        $this->assertSame($name, $relation->name());
        $this->assertSame($constraint, $relation->constraint());
    }
}
