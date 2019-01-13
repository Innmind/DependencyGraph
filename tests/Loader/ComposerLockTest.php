<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Loader\ComposerLock,
    Package,
};
use Innmind\OperatingSystem\Filesystem\Generic;
use Innmind\Url\Path;
use Innmind\Immutable\SetInterface;
use PHPUnit\Framework\TestCase;

class ComposerLockTest extends TestCase
{
    public function testInterface()
    {
        $load = new ComposerLock(new Generic);

        $packages = $load(new Path(__DIR__.'/../../fixtures'));

        $this->assertInstanceOf(SetInterface::class, $packages);
        $this->assertSame(Package::class, (string) $packages->type());
        $this->assertCount(19, $packages);
    }
}
