<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Command\Of,
    Loader\Dependencies,
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
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class OfTest extends TestCase
{
    private $http;

    public function setUp(): void
    {
        $this->http = Transport::curl(Clock::live())->map(
            static fn($config) => $config->limitConcurrencyTo(20),
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new Of(
                new Dependencies(
                    new Package($this->http),
                ),
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
of package --output --help --no-interaction

Generate the dependency graph of the given package
USAGE;

        $this->assertSame(
            $expected,
            (new Of(
                new Dependencies(
                    new Package($this->http),
                ),
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
                    "dot '-Tsvg' '-o' 'innmind_cli_dependencies.svg'",
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
        $command = new Of(
            new Dependencies(
                new Package($this->http),
            ),
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
                ['innmind/cli'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['package', 'innmind/cli'])),
            new Options,
        );

        $console = $command($console)->unwrap();
        $this->assertSame(
            ["innmind_cli_dependencies.svg\n"],
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
                    "dot '-Tsvg' '-o' 'innmind_cli_dependencies.svg'",
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
        $command = new Of(
            new Dependencies(
                new Package($this->http),
            ),
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
                ['innmind/cli'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['package', 'innmind/cli'])),
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
        $command = new Of(
            new Dependencies(
                new Package($this->http),
            ),
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
                ['innmind/cli', '--output'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['package', 'innmind/cli'])),
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
