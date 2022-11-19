<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\Server\Control\Server\{
    Processes,
    Command,
};
use Innmind\CLI\Console;
use Innmind\Immutable\{
    Set,
    Str,
};

final class Save
{
    private Render $render;
    private Processes $processes;

    public function __construct(Render $render, Processes $processes)
    {
        $this->render = $render;
        $this->processes = $processes;
    }

    /**
     * @param Set<Package> $packages
     */
    public function __invoke(
        Console $console,
        Str $file,
        Set $packages,
    ): Console {
        /** @psalm-suppress ArgumentTypeCoercion */
        $process = $this
            ->processes
            ->execute(
                Command::foreground('dot')
                    ->withEnvironments($console->variables()->filter(
                        static fn($name) => $name === 'PATH',
                    ))
                    ->withShortOption('Tsvg')
                    ->withShortOption('o', $file->toString())
                    ->withWorkingDirectory($console->workingDirectory())
                    ->withInput(($this->render)($packages)),
            );

        return $process
            ->wait()
            ->match(
                static fn() => $console->output($file->append("\n")),
                static fn() => $process
                    ->output()
                    ->reduce(
                        $console,
                        static fn(Console $console, $output) => $console->error($output),
                    )
                    ->exit(1),
            );
    }
}
