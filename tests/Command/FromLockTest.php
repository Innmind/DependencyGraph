<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Command\FromLock,
    Loader\ComposerLock,
    Render,
};
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\OperatingSystem\Filesystem\Generic;
use Innmind\Server\Control\Server\Processes;
use Innmind\Url\Path;
use PHPUnit\Framework\TestCase;

class FromLockTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new FromLock(
                new ComposerLock(new Generic),
                new Render,
                $this->createMock(Processes::class)
            )
        );
    }

    public function testUsage()
    {
        $expected = <<<USAGE
from-lock

Generate the dependency graph out of a composer.lock

It will look for a composer.lock in the working directory
USAGE;

        $this->assertSame(
            $expected,
            (string) new FromLock(
                new ComposerLock(new Generic),
                new Render,
                $this->createMock(Processes::class)
            )
        );
    }

    public function testExitWhenFileNotFound()
    {
        $command = new FromLock(
            new ComposerLock(new Generic),
            new Render,
            $processes = $this->createMock(Processes::class)
        );
        $processes
            ->expects($this->never())
            ->method('execute');
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->once())
            ->method('workingDirectory')
            ->willReturn(new Path(__DIR__));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(1);

        $this->assertNull($command(
            $env,
            new Arguments,
            new Options
        ));
    }

    public function testInvokation()
    {
        $command = new FromLock(
            new ComposerLock(new Generic),
            new Render,
            $processes = $this->createMock(Processes::class)
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return (string) $command === "dot '-Tsvg' '-o' 'dependencies.svg'" &&
                    $command->workingDirectory() === __DIR__.'/../../fixtures' &&
                    (string) $command->input() !== '';
            }));
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->any())
            ->method('workingDirectory')
            ->willReturn(new Path(__DIR__.'/../../fixtures'));
        $env
            ->expects($this->never())
            ->method('exit');

        $this->assertNull($command(
            $env,
            new Arguments,
            new Options
        ));
    }
}
