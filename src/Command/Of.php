<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Loader\Dependencies,
    Save,
    Package\Name,
};
use Innmind\CLI\{
    Command,
    Console,
};
use Innmind\Immutable\Str;

final class Of implements Command
{
    private Dependencies $load;
    private Save $save;

    public function __construct(Dependencies $load, Save $save)
    {
        $this->load = $load;
        $this->save = $save;
    }

    public function __invoke(Console $console): Console
    {
        $packages = ($this->load)(Name::of($console->arguments()->get('package')));
        $fileName = Str::of($console->arguments()->get('package'))
            ->replace('/', '_')
            ->append('_dependencies.svg');

        return ($this->save)($console, $fileName, $packages);
    }

    /**
     * @psalm-pure
     */
    public function usage(): string
    {
        return <<<USAGE
of package

Generate the dependency graph of the given package
USAGE;
    }
}
