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
use function Innmind\Immutable\unwrap;

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

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $packages = ($this->load)($vendor = new Name($arguments->get('vendor')));
        $fileName = Str::of("{$vendor->toString()}.svg");

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

        if (!$process->exitCode()->isSuccessful()) {
            $env->exit(1);
            $env->error()->write(Str::of($process->output()->toString()));

            return;
        }

        $env->output()->write($fileName);
    }

    public function toString(): string
    {
        return <<<USAGE
vendor vendor

Generate a graph of all packages of a vendor and their dependencies
USAGE;
    }
}
