<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Command\FromLock,
    Loader\ComposerLock,
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
use Innmind\OperatingSystem\Factory;
use Innmind\Server\Control\{
    Server,
    Server\Process\Builder,
};
use Innmind\Immutable\{
    Attempt,
    Map,
};
use PHPUnit\Framework\TestCase;

class FromLockTest extends TestCase
{
    private $filesystem;

    public function setUp(): void
    {
        $this->filesystem = Factory::build()->filesystem();
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new FromLock(
                new ComposerLock($this->filesystem),
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
from-lock --output --help --no-interaction

Generate the dependency graph out of a composer.lock

It will look for a composer.lock in the working directory
USAGE;

        $this->assertSame(
            $expected,
            (new FromLock(
                new ComposerLock($this->filesystem),
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

    public function testExitWhenFileNotFound()
    {
        $server = Server::via(static fn() => null);
        $command = new FromLock(
            new ComposerLock($this->filesystem),
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
                [],
                [],
                __DIR__.'/',
            ),
            new Arguments,
            new Options,
        );

        $console = $command($console)->unwrap();
        $this->assertSame(
            ["No packages found\n"],
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

    public function testInvokation()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "dot '-Tsvg' '-o' 'dependencies.svg'",
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
        $command = new FromLock(
            new ComposerLock($this->filesystem),
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
                [],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments,
            new Options,
        );

        $console = $command($console)->unwrap();
        $this->assertSame(
            ["dependencies.svg\n"],
            $console
                ->environment()
                ->outputted()
                ->map(static fn($chunk) => $chunk[0]->toString())
                ->toList(),
        );
    }

    public function testExitWithProcessOutputWhenItFails()
    {
        $server = Server::via(
            function($command) {
                $this->assertSame(
                    "dot '-Tsvg' '-o' 'dependencies.svg'",
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
        $command = new FromLock(
            new ComposerLock($this->filesystem),
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
                [],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments,
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
        $command = new FromLock(
            new ComposerLock($this->filesystem),
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
                ['--output'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments,
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
    }
}
