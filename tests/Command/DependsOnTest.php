<?php
declare(strict_types = 1);

namespace Tests\Innmind\DependencyGraph\Command;

use Innmind\DependencyGraph\{
    Command\DependsOn,
    Loader\Dependents,
    Loader\Vendor,
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

class DependsOnTest extends TestCase
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
            new DependsOn(
                new Dependents(
                    new Vendor($this->http, new Package($this->http))
                ),
                new Render,
                $this->createMock(Processes::class)
            )
        );
    }

    public function testUsage()
    {
        $expected = <<<USAGE
depends-on package vendor ...vendors

Generate a graph of all packages depending on a given package

The packages are searched in a given set of vendors. This restriction
is due to the fact that packagist.org doesn't expose via an api the
packages that depends on an other.
USAGE;

        $this->assertSame(
            $expected,
            (string) new DependsOn(
                new Dependents(
                    new Vendor(
                        $this->http,
                        new Package($this->http)
                    )
                ),
                new Render,
                $this->createMock(Processes::class)
            )
        );
    }

    public function testInvokation()
    {
        $command = new DependsOn(
            new Dependents(
                new Vendor(
                    $this->http,
                    new Package($this->http)
                )
            ),
            new Render,
            $processes = $this->createMock(Processes::class)
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function($command): bool {
                return (string) $command === "dot '-Tsvg' '-o' 'innmind_immutable.svg'" &&
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
                    ('package', 'innmind/immutable')
                    ('vendor', 'innmind')
                    ('vendors', Stream::of('string', 'foo'))
            ),
            new Options
        ));
    }
}
