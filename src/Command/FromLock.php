<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Loader\ComposerLock,
    Save,
    Display,
};
use Innmind\CLI\{
    Command,
    Console,
};
use Innmind\Immutable\Str;

final class FromLock implements Command
{
    private ComposerLock $load;
    private Save $save;
    private Display $display;

    public function __construct(ComposerLock $load, Save $save, Display $display)
    {
        $this->load = $load;
        $this->save = $save;
        $this->display = $display;
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

        return $console
            ->options()
            ->maybe('output')
            ->match(
                fn() => ($this->display)($console, $packages),
                fn() => ($this->save)($console, $fileName, $packages),
            );
    }

    /**
     * @psalm-pure
     */
    public function usage(): string
    {
        return <<<USAGE
from-lock --output

Generate the dependency graph out of a composer.lock

It will look for a composer.lock in the working directory
USAGE;
    }
}
