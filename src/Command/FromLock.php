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
    Command\Usage,
    Console,
};
use Innmind\Immutable\{
    Str,
    Attempt,
};

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

    #[\Override]
    public function __invoke(Console $console): Attempt
    {
        $packages = ($this->load)($console->workingDirectory());

        if ($packages->empty()) {
            return $console
                ->exit(1)
                ->error(Str::of("No packages found\n"));
        }

        $fileName = Str::of('dependencies.svg');

        return Attempt::result(
            $console
                ->options()
                ->maybe('output')
                ->match(
                    fn() => ($this->display)($console, $packages),
                    fn() => ($this->save)($console, $fileName, $packages),
                ),
        );
    }

    /**
     * @psalm-pure
     */
    #[\Override]
    public function usage(): Usage
    {
        return Usage::parse(<<<USAGE
from-lock --output

Generate the dependency graph out of a composer.lock

It will look for a composer.lock in the working directory
USAGE);
    }
}
