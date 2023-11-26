<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Command\FromLock,
    Loader\ComposerLock,
    Render,
    Save,
    Display,
};
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
    Console,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Server\Control\Server\{
    Processes,
    Process,
    Process\ExitCode,
    Process\Output,
};
use Innmind\Immutable\{
    Str,
    Either,
    SideEffect,
    Sequence,
    Map,
};
use PHPUnit\Framework\TestCase;

class FromLockTest extends TestCase
{
    private $filesystem;

    public function setUp(): void
    {
        $this->filesystem = Factory::build()->filesystem();
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new FromLock(
                new ComposerLock($this->filesystem),
                new Save(
                    new Render,
                    $this->createMock(Processes::class),
                ),
                new Display(
                    new Render,
                    $this->createMock(Processes::class),
                ),
            ),
        );
    }

    public function testUsage()
    {
        $expected = <<<USAGE
from-lock --output

Generate the dependency graph out of a composer.lock

It will look for a composer.lock in the working directory
USAGE;

        $this->assertSame(
            $expected,
            (new FromLock(
                new ComposerLock($this->filesystem),
                new Save(
                    new Render,
                    $this->createMock(Processes::class),
                ),
                new Display(
                    new Render,
                    $this->createMock(Processes::class),
                ),
            ))->usage(),
        );
    }

    public function testExitWhenFileNotFound()
    {
        $command = new FromLock(
            new ComposerLock($this->filesystem),
            new Save(
                new Render,
                $processes = $this->createMock(Processes::class),
            ),
            new Display(
                new Render,
                $this->createMock(Processes::class),
            ),
        );
        $processes
            ->expects($this->never())
            ->method('execute');
        $console = Console::of(
            Environment\InMemory::of(
                [],
                true,
                [],
                [],
                __DIR__.'/',
            ),
            new Arguments,
            new Options,
        );

        $console = $command($console);
        $this->assertSame([], $console->environment()->outputs());
        $this->assertSame(["No packages found\n"], $console->environment()->errors());
        $this->assertSame(1, $console->environment()->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testInvokation()
    {
        $command = new FromLock(
            new ComposerLock($this->filesystem),
            new Save(
                new Render,
                $processes = $this->createMock(Processes::class),
            ),
            new Display(
                new Render,
                $this->createMock(Processes::class),
            ),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "dot '-Tsvg' '-o' 'dependencies.svg'" &&
                    __DIR__.'/../../fixtures/' === $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ) &&
                    null !== $command->input()->match(
                        static fn($input) => $input->toString(),
                        static fn() => null,
                    );
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $console = Console::of(
            Environment\InMemory::of(
                [],
                true,
                [],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments,
            new Options,
        );

        $console = $command($console);
        $this->assertSame(
            ["dependencies.svg\n"],
            $console->environment()->outputs(),
        );
    }

    public function testExitWithProcessOutputWhenItFails()
    {
        $command = new FromLock(
            new ComposerLock($this->filesystem),
            new Save(
                new Render,
                $processes = $this->createMock(Processes::class),
            ),
            new Display(
                new Render,
                $this->createMock(Processes::class),
            ),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "dot '-Tsvg' '-o' 'dependencies.svg'" &&
                    __DIR__.'/../../fixtures/' === $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ) &&
                    null !== $command->input()->match(
                        static fn($input) => $input->toString(),
                        static fn() => null,
                    );
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left(new ExitCode(1)));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn(new Output\Output(Sequence::of(
                [Str::of('foo'), Output\Type::output],
            )));
        $console = Console::of(
            Environment\InMemory::of(
                [],
                true,
                [],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments,
            new Options,
        );

        $console = $command($console);
        $this->assertSame(
            ['foo'],
            $console->environment()->errors(),
        );
        $this->assertSame(1, $console->environment()->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testOutputSvg()
    {
        $command = new FromLock(
            new ComposerLock($this->filesystem),
            new Save(
                new Render,
                $this->createMock(Processes::class),
            ),
            new Display(
                new Render,
                $processes = $this->createMock(Processes::class),
            ),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "dot '-Tsvg'" &&
                    __DIR__.'/../../fixtures/' === $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ) &&
                    null !== $command->input()->match(
                        static fn($input) => $input->toString(),
                        static fn() => null,
                    );
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn(new Output\Output(Sequence::of(
                [Str::of('<svg>'), Output\Type::output],
                [Str::of('</svg>'), Output\Type::output],
            )));
        $console = Console::of(
            Environment\InMemory::of(
                [],
                true,
                ['--output'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments,
            new Options(Map::of(['output', ''])),
        );

        $console = $command($console);
        $this->assertSame(
            ['<svg>', '</svg>'],
            $console->environment()->outputs(),
        );
    }
}
