<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\Framework\{
    Application,
    Middleware,
};

final class Kernel implements Middleware
{
    #[\Override]
    public function __invoke(Application $app): Application
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return $app
            ->service(Services::render, static fn() => new Render)
            ->service(Services::save, static fn($get, $os) => new Save(
                $get(Services::render),
                $os->control()->processes(),
            ))
            ->service(Services::display, static fn($get, $os) => new Display(
                $get(Services::render),
                $os->control()->processes(),
            ))
            ->service(Services::package, static fn($_, $os) => new Loader\Package($os->remote()->http()))
            ->service(Services::vendor, static fn($get, $os) => new Loader\Vendor(
                $os->remote()->http(),
                $get(Services::package()),
            ))
            ->service(Services::filesystem, static fn($_, $os) => $os->filesystem())
            ->service(Services::processes, static fn($_, $os) => $os->control()->processes())
            ->mapCommand(static fn($command, $get) => new Command\CheckDotInstalled(
                $command,
                $get(Services::processes()),
            ))
            ->command(static fn($get) => new Command\FromLock(
                new Loader\ComposerLock($get(Services::filesystem())),
                $get(Services::save()),
                $get(Services::display()),
            ))
            ->command(static fn($get) => new Command\DependsOn(
                new Loader\Dependents($get(Services::vendor())),
                $get(Services::save()),
                $get(Services::display()),
            ))
            ->command(static fn($get) => new Command\Of(
                new Loader\Dependencies($get(Services::package())),
                $get(Services::save()),
                $get(Services::display()),
            ))
            ->command(static fn($get) => new Command\Vendor(
                new Loader\VendorDependencies(
                    $get(Services::vendor()),
                    $get(Services::package()),
                ),
                $get(Services::save()),
                $get(Services::display()),
            ));
    }
}
