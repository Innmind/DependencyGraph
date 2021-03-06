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
use function Innmind\Immutable\unwrap;

final class FromLock implements Command
{
    private ComposerLock $load;
    private Render $render;
    private Processes $processes;

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

        $fileName = Str::of('dependencies.svg');

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
from-lock

Generate the dependency graph out of a composer.lock

It will look for a composer.lock in the working directory
USAGE;
    }
}
