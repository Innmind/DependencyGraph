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
        $process = $this
            ->processes
            ->execute(
                Command::foreground('dot')
                    ->withShortOption('Tsvg')
                    ->withShortOption('o', $file->toString())
                    ->withWorkingDirectory($console->workingDirectory())
                    ->withInput(($this->render)($packages)),
            );
        $successful = $process->wait()->match(
            static fn() => true,
            static fn() => false,
        );

        if (!$successful) {
            return $process
                ->output()
                ->reduce(
                    $console,
                    static fn(Console $console, $output) => $console->error($output),
                )
                ->exit(1);
        }

        return $console->output($file->append("\n"));
    }
}
