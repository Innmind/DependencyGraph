<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Command\Vendor,
    Loader\VendorDependencies,
    Loader\Vendor as VendorLoader,
    Loader\Package,
    Render,
    Save,
    Display,
};
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
    Console,
};
use Innmind\Server\Control\{
    Server,
    Server\Process\Builder,
};
use Innmind\HttpTransport\Transport;
use Innmind\Time\Clock;
use Innmind\Immutable\{
    Map,
    Attempt,
};
use PHPUnit\Framework\TestCase;

class VendorTest extends TestCase
{
    private $loader;

    public function setUp(): void
    {
        $http = Transport::curl(Clock::live())->map(
            static fn($config) => $config->limitConcurrencyTo(20),
        );
        $this->loader = new VendorDependencies(
            new VendorLoader($http, new Package($http)),
            new Package($http),
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new Vendor(
                $this->loader,
                new Save(
                    new Render,
                    Server::via(static fn() => null)->processes(),
                ),
                new Display(
                    new Render,
                    Server::via(static fn() => null)->processes(),
                ),
            ),
        );
    }

    public function testUsage()
    {
        $expected = <<<USAGE
vendor vendor --output --help --no-interaction

Generate a graph of all packages of a vendor and their dependencies
USAGE;

        $this->assertSame(
            $expected,
            (new Vendor(
                $this->loader,
                new Save(
                    new Render,
                    Server::via(static fn() => null)->processes(),
                ),
                new Display(
                    new Render,
                    Server::via(static fn() => null)->processes(),
                ),
            ))->usage()->toString(),
        );
    }

    public function testInvokation()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "dot '-Tsvg' '-o' 'innmind.svg'",
                    $command->toString(),
                );
                $this->assertSame(
                    __DIR__.'/../../fixtures/',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );
                $this->assertNotNull($command->input()->match(
                    static fn($input) => $input->toString(),
                    static fn() => null,
                ));

                return Attempt::result(
                    Builder::foreground(2)->build(),
                );
            },
        );
        $command = new Vendor(
            $this->loader,
            new Save(
                new Render,
                $server->processes(),
            ),
            new Display(
                new Render,
                $server->processes(),
            ),
        );
        $console = Console::of(
            Environment::inMemory(
                [],
                true,
                ['innmind'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['vendor', 'innmind'])),
            new Options,
        );

        $console = $command($console)->unwrap();
        $this->assertSame(
            ["innmind.svg\n"],
            $console
                ->environment()
                ->outputted()
                ->map(static fn($chunk) => $chunk[0]->toString())
                ->toList(),
        );
        $this->assertNull($console->environment()->exitCode()->match(
            static fn($code) => $code,
            static fn() => null,
        ));
    }

    public function testExitWithProcessOutputWhenItFails()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "dot '-Tsvg' '-o' 'innmind.svg'",
                    $command->toString(),
                );
                $this->assertSame(
                    __DIR__.'/../../fixtures/',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );
                $this->assertNotNull($command->input()->match(
                    static fn($input) => $input->toString(),
                    static fn() => null,
                ));

                return Attempt::result(
                    Builder::foreground(2)
                        ->failed(1, [['foo', 'output']])
                        ->build(),
                );
            },
        );
        $command = new Vendor(
            $this->loader,
            new Save(
                new Render,
                $server->processes(),
            ),
            new Display(
                new Render,
                $server->processes(),
            ),
        );
        $console = Console::of(
            Environment::inMemory(
                [],
                true,
                ['innmind'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['vendor', 'innmind'])),
            new Options,
        );

        $console = $command($console)->unwrap();
        $this->assertSame(
            ['foo'],
            $console
                ->environment()
                ->outputted()
                ->map(static fn($chunk) => $chunk[0]->toString())
                ->toList(),
        );
        $this->assertSame(1, $console->environment()->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testOutputSvg()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "dot '-Tsvg'",
                    $command->toString(),
                );
                $this->assertSame(
                    __DIR__.'/../../fixtures/',
                    $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ),
                );
                $this->assertNotNull($command->input()->match(
                    static fn($input) => $input->toString(),
                    static fn() => null,
                ));

                return Attempt::result(
                    Builder::foreground(2)
                        ->success([
                            ['<svg>', 'output'],
                            ['</svg>', 'output'],
                        ])
                        ->build(),
                );
            },
        );
        $command = new Vendor(
            $this->loader,
            new Save(
                new Render,
                $server->processes(),
            ),
            new Display(
                new Render,
                $server->processes(),
            ),
        );
        $console = Console::of(
            Environment::inMemory(
                [],
                true,
                ['innmind', '--output'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['vendor', 'innmind'])),
            new Options(Map::of(['output', ''])),
        );

        $console = $command($console)->unwrap();
        $this->assertSame(
            ['<svg>', '</svg>'],
            $console
                ->environment()
                ->outputted()
                ->map(static fn($chunk) => $chunk[0]->toString())
                ->toList(),
        );
        $this->assertNull($console->environment()->exitCode()->match(
            static fn($code) => $code,
            static fn() => null,
        ));
    }
}
