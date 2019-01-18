<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\OperatingSystem\Filesystem;
use Innmind\Server\Control\Server\Processes;
use Innmind\HttpTransport\Transport;
use Innmind\CLI\Commands;

function bootstrap(
    Filesystem $filesystem,
    Processes $processes,
    Transport $http
): Commands {
    $render = new Render;

    return new Commands(
        new Command\FromLock(
            new Loader\ComposerLock($filesystem),
            $render,
            $processes
        ),
        new Command\DependsOn(
            new Loader\Dependents(
                new Loader\Vendor(
                    $http,
                    new Loader\Package($http)
                )
            ),
            $render,
            $processes
        )
    );
}
