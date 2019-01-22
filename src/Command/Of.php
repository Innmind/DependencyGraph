<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Loader\Dependencies,
    Render,
    Package\Name,
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

final class Of implements Command
{
    private $load;
    private $render;
    private $processes;

    public function __construct(Dependencies $load, Render $render, Processes $processes)
    {
        $this->load = $load;
        $this->render = $render;
        $this->processes = $processes;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        $packages = ($this->load)(Name::of($arguments->get('package')));
        $fileName = (string) Str::of($arguments->get('package'))
            ->replace('/', '_')
            ->append('_dependencies.svg');

        $process = $this
            ->processes
            ->execute(
                Executable::foreground('dot')
                    ->withShortOption('Tsvg')
                    ->withShortOption('o', $fileName)
                    ->withWorkingDirectory((string) $env->workingDirectory())
                    ->withInput(
                        ($this->render)(...$packages)
                    )
            )
            ->wait();

        if (!$process->exitCode()->isSuccessful()) {
            $env->exit(1);
            $env->error()->write(Str::of((string) $process->output()));
        }
    }

    public function __toString(): string
    {
        return <<<USAGE
of package

Generate the dependency graph of the given package
USAGE;
    }
}
