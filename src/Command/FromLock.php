<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Loader\ComposerLock,
    Render,
};
use Innmind\CLI\{
    Command,
    Console,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command as Executable,
};
use Innmind\Immutable\Str;

final class FromLock implements Command
{
    private ComposerLock $load;
    private Render $render;
    private Processes $processes;

    public function __construct(ComposerLock $load, Render $render, Processes $processes)
    {
        $this->load = $load;
        $this->render = $render;
        $this->processes = $processes;
    }

    public function __invoke(Console $console): Console
    {
        $packages = ($this->load)($console->workingDirectory());

        if ($packages->empty()) {
            return $console
                ->error(Str::of("No packages found\n"))
                ->exit(1);
        }

        $fileName = Str::of('dependencies.svg');

        $process = $this
            ->processes
            ->execute(
                Executable::foreground('dot')
                    ->withShortOption('Tsvg')
                    ->withShortOption('o', $fileName->toString())
                    ->withWorkingDirectory($console->workingDirectory())
                    ->withInput(
                        ($this->render)(...$packages->toList()),
                    ),
            );
        $successful = $process->wait()->match(
            static fn() => true,
            static fn() => false,
        );

        if (!$successful) {
            return $console
                ->error(Str::of($process->output()->toString()))
                ->exit(1);
        }

        return $console->output($fileName->append("\n"));
    }

    /**
     * @psalm-pure
     */
    public function usage(): string
    {
        return <<<USAGE
from-lock

Generate the dependency graph out of a composer.lock

It will look for a composer.lock in the working directory
USAGE;
    }
}
