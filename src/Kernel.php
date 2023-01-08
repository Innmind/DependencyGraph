<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\Framework\{
    Application,
    Middleware,
};

final class Kernel implements Middleware
{
    public function __invoke(Application $app): Application
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return $app
            ->service('render', static fn() => new Render)
            ->service('save', static fn($get, $os) => new Save(
                $get('render'),
                $os->control()->processes(),
            ))
            ->service('display', static fn($get, $os) => new Display(
                $get('render'),
                $os->control()->processes(),
            ))
            ->service('package', static fn($_, $os) => new Loader\Package($os->remote()->http()))
            ->service('vendor', static fn($get, $os) => new Loader\Vendor(
                $os->remote()->http(),
                $get('package'),
            ))
            ->command(static fn($get, $os) => new Command\FromLock(
                new Loader\ComposerLock($os->filesystem()),
                $get('save'),
                $get('display'),
            ))
            ->command(static fn($get) => new Command\DependsOn(
                new Loader\Dependents($get('vendor')),
                $get('save'),
                $get('display'),
            ))
            ->command(static fn($get) => new Command\Of(
                new Loader\Dependencies($get('package')),
                $get('save'),
                $get('display'),
            ))
            ->command(static fn($get) => new Command\Vendor(
                new Loader\VendorDependencies($get('vendor'), $get('package')),
                $get('save'),
                $get('display'),
            ));
    }
}
