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
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Server\Control\Server\{
    Processes,
    Command as Executable,
};
use Innmind\Immutable\Str;

final class Vendor implements Command
{
    private $load;
    private $render;
    private $processes;

    public function __construct(VendorDependencies $load, Render $render, Processes $processes)
    {
        $this->load = $load;
        $this->render = $render;
        $this->processes = $processes;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $packages = ($this->load)($vendor = new Name($arguments->get('vendor')));
        $fileName = Str::of("$vendor.svg");

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
vendor vendor

Generate a graph of all packages of a vendor and their dependencies
USAGE;
    }
}
