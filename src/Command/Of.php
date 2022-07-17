<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Loader\Dependencies,
    Save,
    Display,
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
    private Display $display;

    public function __construct(Dependencies $load, Save $save, Display $display)
    {
        $this->load = $load;
        $this->save = $save;
        $this->display = $display;
    }

    public function __invoke(Console $console): Console
    {
        $packages = ($this->load)(Name::of($console->arguments()->get('package')));
        $fileName = Str::of($console->arguments()->get('package'))
            ->replace('/', '_')
            ->append('_dependencies.svg');

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
of package --output

Generate the dependency graph of the given package
USAGE;
    }
}
