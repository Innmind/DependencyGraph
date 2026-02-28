<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Command\DependsOn,
    Loader\Dependents,
    Loader\Vendor,
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

class DependsOnTest extends TestCase
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
            new DependsOn(
                new Dependents(
                    new Vendor($this->http, new Package($this->http)),
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
depends-on package vendor ...arguments --direct --output --help --no-interaction

Generate a graph of all packages depending on a given package

The packages are searched in a given set of vendors. This restriction
is due to the fact that packagist.org doesn't expose via an api the
packages that depends on an other.
USAGE;

        $this->assertSame(
            $expected,
            (new DependsOn(
                new Dependents(
                    new Vendor(
                        $this->http,
                        new Package($this->http),
                    ),
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
                    "dot '-Tsvg' '-o' 'innmind_immutable_dependents.svg'",
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
        $command = new DependsOn(
            new Dependents(
                new Vendor(
                    $this->http,
                    new Package($this->http),
                ),
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
                ['innmind/immutable', 'innmind'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['package', 'innmind/immutable'], ['vendor', 'innmind'])),
            new Options,
        );

        $console = $command($console)->unwrap();
        $this->assertSame(
            ["innmind_immutable_dependents.svg\n"],
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

    public function testGenerateOnlyDirectDependents()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "dot '-Tsvg' '-o' 'direct_innmind_immutable_dependents.svg'",
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
        $command = new DependsOn(
            new Dependents(
                new Vendor(
                    $this->http,
                    new Package($this->http),
                ),
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
                ['innmind/immutable', 'innmind', '--direct'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['package', 'innmind/immutable'], ['vendor', 'innmind'])),
            new Options(Map::of(['direct', ''])),
        );

        $console = $command($console)->unwrap();
        $this->assertSame(
            ["direct_innmind_immutable_dependents.svg\n"],
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
                    "dot '-Tsvg' '-o' 'innmind_immutable_dependents.svg'",
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
        $command = new DependsOn(
            new Dependents(
                new Vendor(
                    $this->http,
                    new Package($this->http),
                ),
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
                ['innmind/immutable', 'innmind'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['package', 'innmind/immutable'], ['vendor', 'innmind'])),
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
        $command = new DependsOn(
            new Dependents(
                new Vendor(
                    $this->http,
                    new Package($this->http),
                ),
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
                ['innmind/immutable', 'innmind', '--output'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['package', 'innmind/immutable'], ['vendor', 'innmind'])),
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
