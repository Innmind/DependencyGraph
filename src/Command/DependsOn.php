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
    SetInterface,
    Set,
    Str,
};

final class DependsOn implements Command
{
    private $load;
    private $render;
    private $processes;

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
            ...$arguments->get('vendors')->reduce(
                Set::of(Vendor\Name::class),
                static function(SetInterface $vendors, string $vendor): SetInterface {
                    return $vendors->add(new Vendor\Name($vendor));
                }
            )
        );
        $fileName = Str::of((string) $package)
            ->replace('/', '_')
            ->append('_dependents.svg');

        $process = $this
            ->processes
            ->execute(
                Executable::foreground('dot')
                    ->withShortOption('Tsvg')
                    ->withShortOption('o', (string) $fileName)
                    ->withWorkingDirectory((string) $env->workingDirectory())
                    ->withInput(
                        ($this->render)(...$packages)
                    )
            )
            ->wait();

        if (!$process->exitCode()->isSuccessful()) {
            $env->exit(1);
            $env->error()->write(Str::of((string) $process->output()));

            return;
        }

        $env->output()->write($fileName);
    }

    public function __toString(): string
    {
        return <<<USAGE
depends-on package vendor ...vendors

Generate a graph of all packages depending on a given package

The packages are searched in a given set of vendors. This restriction
is due to the fact that packagist.org doesn't expose via an api the
packages that depends on an other.
USAGE;
    }
}
