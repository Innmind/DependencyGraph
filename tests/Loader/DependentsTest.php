<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\Dependents,
    Loader\Vendor,
    Package,
    Vendor as Model,
};
use function Innmind\HttpTransport\bootstrap as http;
use Innmind\Immutable\SetInterface;
use PHPUnit\Framework\TestCase;

class DependentsTest extends TestCase
{
    public function testInvokation()
    {
        $load = new Dependents(
            new Vendor(
                http()['default']()
            )
        );

        $packages = $load(
            Package\Name::of('innmind/immutable'),
            new Model\Name('innmind')
        );

        $this->assertInstanceOf(SetInterface::class, $packages);
        $this->assertSame(Package::class, (string) $packages->type());
        $this->assertCount(53, $packages);
    }
}
