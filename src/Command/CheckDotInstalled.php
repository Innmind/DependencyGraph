<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Command;

use Innmind\CLI\{
    Console,
    Command,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command as Process
};
use Innmind\Immutable\Str;

final class CheckDotInstalled implements Command
{
    private Command $command;
    private Processes $processes;

    public function __construct(Command $command, Processes $processes)
    {
        $this->command = $command;
        $this->processes = $processes;
    }

    public function __invoke(Console $console): Console
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
            ->wait()
            ->match(
                fn() => ($this->command)($console),
                static fn() => $console
                    ->output(Str::of("Graphviz needs to be installed first\n"))
                    ->exit(1),
            );
    }

    /**
     * @psalm-mutation-free
     */
    public function usage(): string
    {
        return $this->command->usage();
    }
}
