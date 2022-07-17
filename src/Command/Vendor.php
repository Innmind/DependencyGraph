<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Loader\VendorDependencies,
    Save,
    Display,
    Vendor\Name,
};
use Innmind\CLI\{
    Command,
    Console,
};
use Innmind\Immutable\Str;

final class Vendor implements Command
{
    private VendorDependencies $load;
    private Save $save;
    private Display $display;

    public function __construct(VendorDependencies $load, Save $save, Display $display)
    {
        $this->load = $load;
        $this->save = $save;
        $this->display = $display;
    }

    public function __invoke(Console $console): Console
    {
        $packages = ($this->load)($vendor = Name::of($console->arguments()->get('vendor')));
        $fileName = Str::of("{$vendor->toString()}.svg");

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
vendor vendor --output

Generate a graph of all packages of a vendor and their dependencies
USAGE;
    }
}
