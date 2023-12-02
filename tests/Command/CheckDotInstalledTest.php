<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\Command\CheckDotInstalled;
use Innmind\CLI\{
    Console,
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Server\Control\Server\{
    Processes,
    Process,
    Process\ExitCode,
    Process\Failed,
    Process\Output\Output,
};
use Innmind\Immutable\{
    Str,
    Either,
    SideEffect,
    Sequence,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class CheckDotInstalledTest extends TestCase
{
    use BlackBox;

    public function testCallCommandIfInstalled()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($usage) {
                $inner = new class($usage) implements Command {
                    public function __construct(private string $usage)
                    {
                    }

                    public function __invoke(Console $console): Console
                    {
                        return $console->output(Str::of('all good'));
                    }

                    public function usage(): string
                    {
                        return $this->usage;
                    }
                };
                $processes = $this->createMock(Processes::class);
                $processes
                    ->expects($this->once())
                    ->method('execute')
                    ->with($this->callback(static function($command) {
                        return $command->toString() === "dot '--help'";
                    }))
                    ->willReturn($process = $this->createMock(Process::class));
                $process
                    ->expects($this->once())
                    ->method('wait')
                    ->willReturn(Either::right(new SideEffect));
                $command = new CheckDotInstalled($inner, $processes);
                $console = Console::of(
                    Environment\InMemory::of(
                        [],
                        true,
                        [],
                        [],
                        '/',
                    ),
                    new Arguments,
                    new Options,
                );

                $console = $command($console);

                $this->assertSame(
                    ['all good'],
                    $console->environment()->outputs(),
                );
            });
    }

    public function testReturnErrorWhenNotInstalled()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($usage) {
                $inner = new class($usage) implements Command {
                    public function __construct(private string $usage)
                    {
                    }

                    public function __invoke(Console $console): Console
                    {
                        return $console->output(Str::of('all good'));
                    }

                    public function usage(): string
                    {
                        return $this->usage;
                    }
                };
                $processes = $this->createMock(Processes::class);
                $processes
                    ->expects($this->once())
                    ->method('execute')
                    ->with($this->callback(static function($command) {
                        return $command->toString() === "dot '--help'";
                    }))
                    ->willReturn($process = $this->createMock(Process::class));
                $process
                    ->expects($this->once())
                    ->method('wait')
                    ->willReturn(Either::left(new Failed(
                        new ExitCode(127),
                        new Output(Sequence::of()),
                    )));
                $command = new CheckDotInstalled($inner, $processes);
                $console = Console::of(
                    Environment\InMemory::of(
                        [],
                        true,
                        [],
                        [],
                        '/',
                    ),
                    new Arguments,
                    new Options,
                );

                $console = $command($console);

                $this->assertSame(
                    1,
                    $console->environment()->exitCode()->match(
                        static fn($exit) => $exit->toInt(),
                        static fn() => null,
                    ),
                );
                $this->assertSame(
                    ["Graphviz needs to be installed first\n"],
                    $console->environment()->outputs(),
                );
            });
    }

    public function testUsage()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($usage) {
                $inner = new class($usage) implements Command {
                    public function __construct(private string $usage)
                    {
                    }

                    public function __invoke(Console $console): Console
                    {
                        return $console;
                    }

                    public function usage(): string
                    {
                        return $this->usage;
                    }
                };
                $processes = $this->createMock(Processes::class);
                $command = new CheckDotInstalled($inner, $processes);

                $this->assertSame(
                    $usage,
                    $command->usage(),
                );
            });
    }
}
