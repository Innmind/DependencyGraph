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
    Command\Usage,
    Console,
};
use Innmind\Immutable\{
    Str,
    Attempt,
};

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

    #[\Override]
    public function __invoke(Console $console): Attempt
    {
        $packages = ($this->load)($vendor = Name::of($console->arguments()->get('vendor')));
        $fileName = Str::of("{$vendor->toString()}.svg");

        return Attempt::result(
            $console
                ->options()
                ->maybe('output')
                ->match(
                    fn() => ($this->display)($console, $packages),
                    fn() => ($this->save)($console, $fileName, $packages),
                ),
        );
    }

    /**
     * @psalm-pure
     */
    #[\Override]
    public function usage(): Usage
    {
        return Usage::parse(<<<USAGE
vendor vendor --output

Generate a graph of all packages of a vendor and their dependencies
USAGE);
    }
}
