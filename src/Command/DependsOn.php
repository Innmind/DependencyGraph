<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Loader\Dependents,
    Package,
    Vendor,
    Save,
    Display,
};
use Innmind\CLI\{
    Command,
    Console,
};
use Innmind\Immutable\{
    Set,
    Str,
};

final class DependsOn implements Command
{
    private Dependents $load;
    private Save $save;
    private Display $display;

    public function __construct(Dependents $load, Save $save, Display $display)
    {
        $this->load = $load;
        $this->save = $save;
        $this->display = $display;
    }

    public function __invoke(Console $console): Console
    {
        /** @psalm-suppress MixedArgument Due to the reduce */
        $vendors = $console
            ->arguments()
            ->pack()
            ->reduce(
                Set::of($console->arguments()->get('vendor')),
                static fn(Set $vendors, $vendor) => ($vendors)($vendor),
            )
            ->map(static fn($vendor) => new Vendor\Name($vendor));
        $packages = ($this->load)(
            $package = Package\Name::of($console->arguments()->get('package')),
            $vendors,
        );

        $fileName = Str::of($package->toString())
            ->replace('/', '_')
            ->append('_dependents.svg');

        if ($console->options()->contains('direct')) {
            $packages = $packages
                ->filter(static function(Package $dependents) use ($package): bool {
                    return $dependents->dependsOn($package) || $dependents->name()->equals($package);
                })
                ->map(static function(Package $dependents) use ($package): Package {
                    return $dependents->keep(Set::of($package));
                });
            $fileName = $fileName->prepend('direct_');
        }

        return $console
            ->options()
            ->maybe('output')
            ->match(
                fn() => ($this->display)($console, $packages),
                fn() => ($this->save)($console, $fileName, $packages),
            );
    }

    /**
     * @psalm-pure
     */
    public function usage(): string
    {
        return <<<USAGE
depends-on package vendor ...vendors --direct --output

Generate a graph of all packages depending on a given package

The packages are searched in a given set of vendors. This restriction
is due to the fact that packagist.org doesn't expose via an api the
packages that depends on an other.
USAGE;
    }
}
