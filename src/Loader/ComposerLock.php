<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Package,
    Package\Name,
    Package\Version,
    Package\Relation,
    Package\Constraint,
};
use Innmind\Url\{
    Path,
    Url,
};
use Innmind\Json\Json;
use Innmind\OperatingSystem\Filesystem;
use Innmind\Filesystem\{
    File,
    Name as FileName,
};
use Innmind\Validation\{
    Is,
    Constraint as Rule,
};
use Innmind\Immutable\{
    Set,
    Maybe,
    Predicate\Instance,
};

final class ComposerLock
{
    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @return Set<Package>
     */
    public function __invoke(Path $path): Set
    {
        /**
         * @psalm-suppress MixedArgument
         * @var Rule<mixed, Set<Package>>
         */
        $validate = Is::shape(
            'packages',
            Is::list(
                Is::shape(
                    'name',
                    Is::string()
                        ->map(Name::maybe(...)),
                )
                    ->optional(
                        'source',
                        Is::shape(
                            'url',
                            Is::string()
                                ->map(static fn($value) => \rtrim($value, '.git').'/')
                                ->map(Url::maybe(...)),
                        )
                            ->optional('url')
                            ->default('url', null)
                            ->map(static fn($source) => Maybe::of($source['url']))
                            ->map(static fn($source) => $source->flatMap(
                                static fn(Maybe $source) => $source,
                            )),
                    )
                    ->with(
                        'version',
                        Is::string()
                            ->map(Version::maybe(...)),
                    )
                    ->optional(
                        'require',
                        Is::associativeArray(
                            Is::string()->map(Name::maybe(...)),
                            Is::string()->map(Constraint::maybe(...)),
                        )
                            ->map(
                                static fn($requires) => $requires
                                    ->map(Maybe::all(...))
                                    ->values()
                                    ->flatMap(
                                        static fn($maybe) => $maybe
                                            ->map(Relation::of(...))
                                            ->toSequence(),
                                    )
                                    ->toSet(),
                            ),
                    )
                    ->rename('require', 'relations')
                    ->map(
                        static fn($package) => Maybe::all(
                            $package['name'],
                            $package['version'],
                            $package['source'],
                        )->map(static fn(Name $name, Version $version, Url $repository) => new Package(
                            $name,
                            $version,
                            Url::of('https://packagist.org/packages/'.$name->toString()),
                            $repository,
                            $package['relations'],
                        )),
                    ),
            )->map(
                static fn($packages) => Set::of(...$packages)->flatMap(
                    static fn($package) => $package
                        ->toSequence()
                        ->toSet(),
                ),
            ),
        )
            ->map(static fn($content): mixed => $content['packages'])
            ->map(static function(Set $packages) {
                $installed = $packages->map(
                    static fn(Package $package): Name => $package->name(),
                );

                return $packages->map(
                    static fn(Package $package): Package => $package->keep($installed),
                );
            });

        return $this
            ->filesystem
            ->mount($path)
            ->get(FileName::of('composer.lock'))
            ->keep(Instance::of(File::class))
            ->map(static fn($file) => $file->content()->toString())
            ->map(Json::decode(...))
            ->flatMap(static fn($lock) => $validate($lock)->maybe())
            ->match(
                static fn($packages) => $packages,
                static fn() => Set::of(),
            );
    }
}
