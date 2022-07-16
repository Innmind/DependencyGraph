<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Loader\VendorDependencies,
    Render,
    Vendor\Name,
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

final class Vendor implements Command
{
    private VendorDependencies $load;
    private Render $render;
    private Processes $processes;

    public function __construct(VendorDependencies $load, Render $render, Processes $processes)
    {
        $this->load = $load;
        $this->render = $render;
        $this->processes = $processes;
    }

    public function __invoke(Console $console): Console
    {
        $packages = ($this->load)($vendor = new Name($console->arguments()->get('vendor')));
        $fileName = Str::of("{$vendor->toString()}.svg");

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
vendor vendor

Generate a graph of all packages of a vendor and their dependencies
USAGE;
    }
}
