<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\Command\CheckDotInstalled;
use Innmind\CLI\{
    Console,
    Command,
    Command\Arguments,
    Command\Options,
    Command\Usage,
    Environment,
};
use Innmind\Server\Control\{
    Server,
    Server\Process\Builder,
};
use Innmind\Immutable\{
    Str,
    Attempt,
};
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class CheckDotInstalledTest extends TestCase
{
    use BlackBox;

    public function testCallCommandIfInstalled(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::of('some', 'command', 'name'))
            ->prove(function($usage) {
                $inner = new class($usage) implements Command {
                    public function __construct(private string $usage)
                    {
                    }

                    public function __invoke(Console $console): Attempt
                    {
                        return $console->output(Str::of('all good'));
                    }

                    public function usage(): Usage
                    {
                        return Usage::parse($this->usage);
                    }
                };
                $server = Server::via(
                    function($command) {
                        $this->assertSame("dot '--help'", $command->toString());

                        return Attempt::result(
                            Builder::foreground(2)->build(),
                        );
                    },
                );
                $command = new CheckDotInstalled($inner, $server->processes());
                $console = Console::of(
                    Environment::inMemory(
                        [],
                        true,
                        [],
                        [],
                        '/',
                    ),
                    new Arguments,
                    new Options,
                );

                $console = $command($console)->unwrap();

                $this->assertSame(
                    ['all good'],
                    $console
                        ->environment()
                        ->outputted()
                        ->map(static fn($chunk) => $chunk[0]->toString())
                        ->toList(),
                );
            });
    }

    public function testReturnErrorWhenNotInstalled(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::of('some', 'command', 'name'))
            ->prove(function($usage) {
                $inner = new class($usage) implements Command {
                    public function __construct(private string $usage)
                    {
                    }

                    public function __invoke(Console $console): Attempt
                    {
                        return $console->output(Str::of('all good'));
                    }

                    public function usage(): Usage
                    {
                        return Usage::parse($this->usage);
                    }
                };
                $server = Server::via(
                    function($command) {
                        $this->assertSame("dot '--help'", $command->toString());

                        return Attempt::result(
                            Builder::foreground(2)
                                ->failed(127)
                                ->build(),
                        );
                    },
                );
                $command = new CheckDotInstalled($inner, $server->processes());
                $console = Console::of(
                    Environment::inMemory(
                        [],
                        true,
                        [],
                        [],
                        '/',
                    ),
                    new Arguments,
                    new Options,
                );

                $console = $command($console)->unwrap();

                $this->assertSame(
                    1,
                    $console->environment()->exitCode()->match(
                        static fn($exit) => $exit->toInt(),
                        static fn() => null,
                    ),
                );
                $this->assertSame(
                    ["Graphviz needs to be installed first\n"],
                    $console
                        ->environment()
                        ->outputted()
                        ->map(static fn($chunk) => $chunk[0]->toString())
                        ->toList(),
                );
            });
    }

    public function testUsage(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::of('some', 'command', 'name'))
            ->prove(function($usage) {
                $inner = new class($usage) implements Command {
                    public function __construct(private string $usage)
                    {
                    }

                    public function __invoke(Console $console): Attempt
                    {
                        return $console;
                    }

                    public function usage(): Usage
                    {
                        return Usage::parse($this->usage);
                    }
                };
                $server = Server::via(
                    static fn() => Attempt::result(
                        Builder::foreground(2)->build(),
                    ),
                );
                $command = new CheckDotInstalled($inner, $server->processes());

                $this->assertSame(
                    "$usage --help --no-interaction",
                    $command->usage()->toString(),
                );
            });
    }
}
