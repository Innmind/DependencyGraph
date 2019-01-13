<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\OperatingSystem\Filesystem;
use Innmind\Server\Control\Server\Processes;
use Innmind\CLI\Commands;

function bootstrap(Filesystem $filesystem, Processes $processes): Commands
{
    return new Commands(
        new Command\FromLock(
            new Loader\ComposerLock($filesystem),
            new Render,
            $processes
        )
    );
}
