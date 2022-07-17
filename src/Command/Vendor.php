<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Loader\VendorDependencies,
    Save,
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

    public function __construct(VendorDependencies $load, Save $save)
    {
        $this->load = $load;
        $this->save = $save;
    }

    public function __invoke(Console $console): Console
    {
        $packages = ($this->load)($vendor = new Name($console->arguments()->get('vendor')));
        $fileName = Str::of("{$vendor->toString()}.svg");

        return ($this->save)($console, $fileName, $packages);
    }

    /**
     * @psalm-pure
     */
    public function usage(): string
    {
        return <<<USAGE
vendor vendor

Generate a graph of all packages of a vendor and their dependencies
USAGE;
    }
}
