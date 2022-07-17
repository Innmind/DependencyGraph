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
    Transport $http,
): Commands {
    $save = new Save(new Render, $processes);
    $package = new Loader\Package($http);
    $vendor = new Loader\Vendor($http, $package);

    return Commands::of(
        new Command\FromLock(
            new Loader\ComposerLock($filesystem),
            $save,
        ),
        new Command\DependsOn(
            new Loader\Dependents($vendor),
            $save,
        ),
        new Command\Of(
            new Loader\Dependencies($package),
            $save,
        ),
        new Command\Vendor(
            new Loader\VendorDependencies($vendor, $package),
            $save,
        ),
    );
}
