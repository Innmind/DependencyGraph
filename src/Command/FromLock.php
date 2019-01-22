<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Loader\ComposerLock,
    Render,
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
use Innmind\Filesystem\Exception\FileNotFound;
use Innmind\Immutable\Str;

final class FromLock implements Command
{
    private $load;
    private $render;
    private $processes;

    public function __construct(ComposerLock $load, Render $render, Processes $processes)
    {
        $this->load = $load;
        $this->render = $render;
        $this->processes = $processes;
    }

    public function __invoke(Environment $env, Arguments $arguments, Options $options): void
    {
        try {
            $packages = ($this->load)($env->workingDirectory());
        } catch (FileNotFound $e) {
            $env->error()->write(Str::of('No composer.lock found'));
            $env->exit(1);

            return;
        }

        $process = $this
            ->processes
            ->execute(
                Executable::foreground('dot')
                    ->withShortOption('Tsvg')
                    ->withShortOption('o', 'dependencies.svg')
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
from-lock

Generate the dependency graph out of a composer.lock

It will look for a composer.lock in the working directory
USAGE;
    }
}
