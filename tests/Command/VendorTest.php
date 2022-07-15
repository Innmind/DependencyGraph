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
};
use Innmind\Server\Control\Server\{
    Processes,
    Process,
    Process\ExitCode,
    Process\Output,
};
use function Innmind\HttpTransport\bootstrap as http;
use Innmind\Url\Path;
use Innmind\Stream\Writable;
use Innmind\Immutable\{
    Map,
    Stream,
    Str,
};
use PHPUnit\Framework\TestCase;

class VendorTest extends TestCase
{
    private $loader;

    public function setUp(): void
    {
        $http = http()['default']();
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
            ))->toString(),
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
                    $command->workingDirectory()->toString() === __DIR__.'/../../fixtures' &&
                    $command->input()->toString() !== '';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->any())
            ->method('workingDirectory')
            ->willReturn(Path::of(__DIR__.'/../../fixtures'));
        $env
            ->expects($this->never())
            ->method('exit');

        $this->assertNull($command(
            $env,
            new Arguments(
                Map::of('string', 'string')
                    ('vendor', 'innmind'),
            ),
            new Options,
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
                    $command->workingDirectory()->toString() === __DIR__.'/../../fixtures' &&
                    $command->input()->toString() !== '';
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait');
        $process
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(1));
        $process
            ->expects($this->once())
            ->method('output')
            ->willReturn($output = $this->createMock(Output::class));
        $output
            ->expects($this->once())
            ->method('toString')
            ->willReturn('foo');
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->any())
            ->method('workingDirectory')
            ->willReturn(Path::of(__DIR__.'/../../fixtures'));
        $env
            ->expects($this->once())
            ->method('exit')
            ->with(1);
        $env
            ->expects($this->once())
            ->method('error')
            ->willReturn($error = $this->createMock(Writable::class));
        $error
            ->expects($this->once())
            ->method('write')
            ->with(Str::of('foo'));
        $env
            ->expects($this->never())
            ->method('output');

        $this->assertNull($command(
            $env,
            new Arguments(
                Map::of('string', 'string')
                    ('vendor', 'innmind'),
            ),
            new Options,
        ));
    }
}
