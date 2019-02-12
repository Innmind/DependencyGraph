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
    $package = new Loader\Package($http);
    $vendor = new Loader\Vendor($http, $package);

    return new Commands(
        new Command\FromLock(
            new Loader\ComposerLock($filesystem),
            $render,
            $processes
        ),
        new Command\DependsOn(
            new Loader\Dependents($vendor),
            $render,
            $processes
        ),
        new Command\Of(
            new Loader\Dependencies($package),
            $render,
            $processes
        ),
        new Command\Vendor(
            new Loader\VendorDependencies($vendor, $package),
            $render,
            $processes
        )
    );
}
