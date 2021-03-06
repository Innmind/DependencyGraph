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
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command as Executable,
};
use Innmind\Immutable\{
    Set,
    Str,
};
use function Innmind\Immutable\unwrap;

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

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $packages = ($this->load)(
            $package = Package\Name::of($arguments->get('package')),
            new Vendor\Name($arguments->get('vendor')),
            ...unwrap($arguments->pack()->mapTo(
                Vendor\Name::class,
                static fn(string $vendor): Vendor\Name => new Vendor\Name($vendor),
            )),
        );

        $fileName = Str::of($package->toString())
            ->replace('/', '_')
            ->append('_dependents.svg');

        if ($options->contains('direct')) {
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
                    ->withWorkingDirectory($env->workingDirectory())
                    ->withInput(
                        ($this->render)(...unwrap($packages)),
                    ),
            );
        $process->wait();

        if (!$process->exitCode()->successful()) {
            $env->exit(1);
            $env->error()->write(Str::of($process->output()->toString()));

            return;
        }

        $env->output()->write($fileName);
    }

    public function toString(): string
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
