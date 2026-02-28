<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Command;

use Innmind\CLI\{
    Console,
    Command,
    Command\Usage,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command as Process
};
use Innmind\Immutable\{
    Str,
    Attempt,
};

final class CheckDotInstalled implements Command
{
    private Command $command;
    private Processes $processes;

    public function __construct(Command $command, Processes $processes)
    {
        $this->command = $command;
        $this->processes = $processes;
    }

    #[\Override]
    public function __invoke(Console $console): Attempt
    {
        /** @psalm-suppress ArgumentTypeCoercion Due to the environment variables */
        return $this
            ->processes
            ->execute(
                Process::foreground('dot')
                    ->withOption('help')
                    ->withEnvironments($console->variables()->filter(
                        static fn($name) => $name === 'PATH',
                    )),
            )
            ->flatMap(
                static fn($process) => $process
                    ->wait()
                    ->attempt(static fn() => new \Exception),
            )
            ->match(
                fn() => ($this->command)($console),
                static fn() => $console
                    ->exit(1)
                    ->output(Str::of("Graphviz needs to be installed first\n")),
            );
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function usage(): Usage
    {
        return $this->command->usage();
    }
}
