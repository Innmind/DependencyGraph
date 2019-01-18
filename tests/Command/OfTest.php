<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Command\Of,
    Loader\Dependencies,
    Loader\Package,
    Render,
};
use Innmind\CLI\{
    Command,
    Command\Arguments,
    Command\Options,
    Environment,
};
use Innmind\Server\Control\Server\Processes;
use function Innmind\HttpTransport\bootstrap as http;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Map,
    Stream,
};
use PHPUnit\Framework\TestCase;

class OfTest extends TestCase
{
    private $http;

    public function setUp()
    {
        $this->http = http()['default']();
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Command::class,
            new Of(
                new Dependencies(
                    new Package($this->http)
                ),
                new Render,
                $this->createMock(Processes::class)
            )
        );
    }

    public function testUsage()
    {
        $expected = <<<USAGE
of package

Generate the dependency graph of the given package
USAGE;

        $this->assertSame(
            $expected,
            (string) new Of(
                new Dependencies(
                    new Package($this->http)
                ),
                new Render,
                $this->createMock(Processes::class)
            )
        );
    }

    public function testInvokation()
    {
        $command = new Of(
            new Dependencies(
                new Package($this->http)
            ),
            new Render,
            $processes = $this->createMock(Processes::class)
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return (string) $command === "dot '-Tsvg' '-o' 'innmind_cli_dependencies.svg'" &&
                    $command->workingDirectory() === __DIR__.'/../../fixtures' &&
                    (string) $command->input() !== '';
            }));
        $env = $this->createMock(Environment::class);
        $env
            ->expects($this->any())
            ->method('workingDirectory')
            ->willReturn(new Path(__DIR__.'/../../fixtures'));
        $env
            ->expects($this->never())
            ->method('exit');

        $this->assertNull($command(
            $env,
            new Arguments(
                Map::of('string', 'mixed')
                    ('package', 'innmind/cli')
            ),
            new Options
        ));
    }
}
