<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph;

use function Innmind\DependencyGraph\bootstrap;
use Innmind\OperatingSystem\Filesystem;
use Innmind\Server\Control\Server\Processes;
use Innmind\CLI\Commands;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testInterface()
    {
        $commands = bootstrap(
            $this->createMock(Filesystem::class),
            $this->createMock(Processes::class)
        );

        $this->assertInstanceOf(Commands::class, $commands);
    }
}
