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
    Sequence,
    Str,
    Either,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class DependsOnTest extends TestCase
{
    private $http;

    public function setUp(): void
    {
        $this->http = Curl::of(new Clock);
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
                    $this->createMock(Processes::class),
                ),
            ),
        );
    }

    public function testUsage()
    {
        $expected = <<<USAGE
depends-on package vendor ...vendors --direct

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
                    $this->createMock(Processes::class),
                ),
            ))->usage(),
        );
    }

    public function testInvokation()
    {
        $command = new DependsOn(
            new Dependents(
                new Vendor(
                    $this->http,
                    new Package($this->http),
                ),
            ),
            new Save(
                new Render,
                $processes = $this->createMock(Processes::class),
            ),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "dot '-Tsvg' '-o' 'innmind_immutable_dependents.svg'" &&
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
                ['innmind/immutable', 'innmind'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['package', 'innmind/immutable'], ['vendor', 'innmind'])),
            new Options,
        );

        $console = $command($console);
        $this->assertSame(
            ["innmind_immutable_dependents.svg\n"],
            $console->environment()->outputs(),
        );
        $this->assertNull($console->environment()->exitCode()->match(
            static fn($code) => $code,
            static fn() => null,
        ));
    }

    public function testGenerateOnlyDirectDependents()
    {
        $command = new DependsOn(
            new Dependents(
                new Vendor(
                    $this->http,
                    new Package($this->http),
                ),
            ),
            new Save(
                new Render,
                $processes = $this->createMock(Processes::class),
            ),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "dot '-Tsvg' '-o' 'direct_innmind_immutable_dependents.svg'" &&
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
                ['innmind/immutable', 'innmind', '--direct'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['package', 'innmind/immutable'], ['vendor', 'innmind'])),
            new Options(Map::of(['direct', ''])),
        );

        $console = $command($console);
        $this->assertSame(
            ["direct_innmind_immutable_dependents.svg\n"],
            $console->environment()->outputs(),
        );
        $this->assertNull($console->environment()->exitCode()->match(
            static fn($code) => $code,
            static fn() => null,
        ));
    }

    public function testExitWithProcessOutputWhenItFails()
    {
        $command = new DependsOn(
            new Dependents(
                new Vendor(
                    $this->http,
                    new Package($this->http),
                ),
            ),
            new Save(
                new Render,
                $processes = $this->createMock(Processes::class),
            ),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "dot '-Tsvg' '-o' 'innmind_immutable_dependents.svg'" &&
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
                ['innmind/immutable', 'innmind'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['package', 'innmind/immutable'], ['vendor', 'innmind'])),
            new Options,
        );

        $console = $command($console);
        $this->assertSame([], $console->environment()->outputs());
        $this->assertSame(['foo'], $console->environment()->errors());
        $this->assertSame(1, $console->environment()->exitCode()->match(
            static fn($code) => $code->toInt(),
            static fn() => null,
        ));
    }
}
