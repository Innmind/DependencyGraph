<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Loader\Dependents,
    Render,
    Package,
    Vendor,
};
use Innmind\CLI\{
    Command,
    Console,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command as Executable,
};
use Innmind\Immutable\{
    Set,
    Str,
};

final class DependsOn implements Command
{
    private Dependents $load;
    private Render $render;
    private Processes $processes;

    public function __construct(Dependents $load, Render $render, Processes $processes)
    {
        $this->load = $load;
        $this->render = $render;
        $this->processes = $processes;
    }

    public function __invoke(Console $console): Console
    {
        $packages = ($this->load)(
            $package = Package\Name::of($console->arguments()->get('package')),
            new Vendor\Name($console->arguments()->get('vendor')),
            ...$console
                ->arguments()
                ->pack()
                ->map(static fn(string $vendor): Vendor\Name => new Vendor\Name($vendor))
                ->toList(),
        );

        $fileName = Str::of($package->toString())
            ->replace('/', '_')
            ->append('_dependents.svg');

        if ($console->options()->contains('direct')) {
            $packages = $packages
                ->filter(static function(Package $dependents) use ($package): bool {
                    return $dependents->dependsOn($package) || $dependents->name()->equals($package);
                })
                ->map(static function(Package $dependents) use ($package): Package {
                    return $dependents->keep($package);
                });
            $fileName = $fileName->prepend('direct_');
        }

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

        return $console->output($fileName);
    }

    /**
     * @psalm-pure
     */
    public function usage(): string
    {
        return <<<USAGE
depends-on package vendor ...vendors --direct

Generate a graph of all packages depending on a given package

The packages are searched in a given set of vendors. This restriction
is due to the fact that packagist.org doesn't expose via an api the
packages that depends on an other.
USAGE;
    }
}
