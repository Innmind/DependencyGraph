<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Loader\ComposerLock,
    Save,
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

    public function __construct(ComposerLock $load, Save $save)
    {
        $this->load = $load;
        $this->save = $save;
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

        return ($this->save)($console, $fileName, $packages);
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
