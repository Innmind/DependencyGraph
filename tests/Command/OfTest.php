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
use Innmind\Server\Control\Server\{
    Processes,
    Process,
    Process\ExitCode,
    Process\Output,
};
use Innmind\HttpTransport\Curl;
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\Immutable\{
    Map,
    Str,
    Either,
    SideEffect,
    Sequence,
};
use PHPUnit\Framework\TestCase;

class OfTest extends TestCase
{
    private $http;

    public function setUp(): void
    {
        $this->http = Curl::of(new Clock)->maxConcurrency(20);
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
                    $this->createMock(Processes::class),
                ),
                new Display(
                    new Render,
                    $this->createMock(Processes::class),
                ),
            ),
        );
    }

    public function testUsage()
    {
        $expected = <<<USAGE
of package --output

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
                    $this->createMock(Processes::class),
                ),
                new Display(
                    new Render,
                    $this->createMock(Processes::class),
                ),
            ))->usage(),
        );
    }

    public function testInvokation()
    {
        $command = new Of(
            new Dependencies(
                new Package($this->http),
            ),
            new Save(
                new Render,
                $processes = $this->createMock(Processes::class),
            ),
            new Display(
                new Render,
                $this->createMock(Processes::class),
            ),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "dot '-Tsvg' '-o' 'innmind_cli_dependencies.svg'" &&
                    __DIR__.'/../../fixtures/' === $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ) &&
                    null !== $command->input()->match(
                        static fn($input) => $input->toString(),
                        static fn() => null,
                    );
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $console = Console::of(
            Environment\InMemory::of(
                [],
                true,
                ['innmind/cli'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['package', 'innmind/cli'])),
            new Options,
        );

        $console = $command($console);
        $this->assertSame(
            ["innmind_cli_dependencies.svg\n"],
            $console->environment()->outputs(),
        );
        $this->assertNull($console->environment()->exitCode()->match(
            static fn($code) => $code,
            static fn() => null,
        ));
    }

    public function testExitWithProcessOutputWhenItFails()
    {
        $command = new Of(
            new Dependencies(
                new Package($this->http),
            ),
            new Save(
                new Render,
                $processes = $this->createMock(Processes::class),
            ),
            new Display(
                new Render,
                $this->createMock(Processes::class),
            ),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "dot '-Tsvg' '-o' 'innmind_cli_dependencies.svg'" &&
                    __DIR__.'/../../fixtures/' === $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ) &&
                    null !== $command->input()->match(
                        static fn($input) => $input->toString(),
                        static fn() => null,
                    );
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left(new ExitCode(1)));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn(new Output\Output(Sequence::of(
                [Str::of('foo'), Output\Type::output],
            )));
        $console = Console::of(
            Environment\InMemory::of(
                [],
                true,
                ['innmind/cli'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['package', 'innmind/cli'])),
            new Options,
        );

        $console = $command($console);
        $this->assertSame([], $console->environment()->outputs());
        $this->assertSame(
            ['foo'],
            $console->environment()->errors(),
        );
        $this->assertSame(1, $console->environment()->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }

    public function testOutputSvg()
    {
        $command = new Of(
            new Dependencies(
                new Package($this->http),
            ),
            new Save(
                new Render,
                $this->createMock(Processes::class),
            ),
            new Display(
                new Render,
                $processes = $this->createMock(Processes::class),
            ),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "dot '-Tsvg'" &&
                    __DIR__.'/../../fixtures/' === $command->workingDirectory()->match(
                        static fn($path) => $path->toString(),
                        static fn() => null,
                    ) &&
                    null !== $command->input()->match(
                        static fn($input) => $input->toString(),
                        static fn() => null,
                    );
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn(new Output\Output(Sequence::of(
                [Str::of('<svg>'), Output\Type::output],
                [Str::of('</svg>'), Output\Type::output],
            )));
        $console = Console::of(
            Environment\InMemory::of(
                [],
                true,
                ['innmind/cli', '--output'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['package', 'innmind/cli'])),
            new Options(Map::of(['output', ''])),
        );

        $console = $command($console);
        $this->assertSame(
            ['<svg>', '</svg>'],
            $console->environment()->outputs(),
        );
        $this->assertNull($console->environment()->exitCode()->match(
            static fn($code) => $code,
            static fn() => null,
        ));
    }
}
