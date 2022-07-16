<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Command\Vendor,
    Loader\VendorDependencies,
    Loader\Vendor as VendorLoader,
    Loader\Package,
    Render,
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

class VendorTest extends TestCase
{
    private $loader;

    public function setUp(): void
    {
        $http = Curl::of(new Clock);
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
                new Render,
                $this->createMock(Processes::class),
            ),
        );
    }

    public function testUsage()
    {
        $expected = <<<USAGE
vendor vendor

Generate a graph of all packages of a vendor and their dependencies
USAGE;

        $this->assertSame(
            $expected,
            (new Vendor(
                $this->loader,
                new Render,
                $this->createMock(Processes::class),
            ))->usage(),
        );
    }

    public function testInvokation()
    {
        $command = new Vendor(
            $this->loader,
            new Render,
            $processes = $this->createMock(Processes::class),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "dot '-Tsvg' '-o' 'innmind.svg'" &&
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
                ['innmind'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['vendor', 'innmind'])),
            new Options,
        );

        $console = $command($console);
        $this->assertSame(
            ['innmind.svg'],
            $console->environment()->outputs(),
        );
        $this->assertNull($console->environment()->exitCode()->match(
            static fn($code) => $code,
            static fn() => null,
        ));
    }

    public function testExitWithProcessOutputWhenItFails()
    {
        $command = new Vendor(
            $this->loader,
            new Render,
            $processes = $this->createMock(Processes::class),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return $command->toString() === "dot '-Tsvg' '-o' 'innmind.svg'" &&
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
                ['innmind'],
                [],
                __DIR__.'/../../fixtures',
            ),
            new Arguments(Map::of(['vendor', 'innmind'])),
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
}
