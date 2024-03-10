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
use Innmind\Immutable\{
    Set,
    Maybe,
    Predicate\Instance,
};

/**
 * @psalm-type Lock = array{packages: list<array{name: string, version: string, require?: array<string, string>}>}
 */
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
        return $this
            ->filesystem
            ->mount($path)
            ->get(FileName::of('composer.lock'))
            ->keep(Instance::of(File::class))
            ->map($this->decode(...))
            ->map($this->denormalize(...))
            ->match(
                static fn($packages) => $packages,
                static fn() => Set::of(),
            );
    }

    /**
     * @return Lock
     */
    private function decode(File $file): array
    {
        /** @var Lock */
        return Json::decode($file->content()->toString());
    }

    /**
     * @param Lock $composer
     *
     * @return Set<Package>
     */
    private function denormalize(array $composer): Set
    {
        /** @var Set<Package> */
        $packages = Set::of();

        foreach ($composer['packages'] as $package) {
            /** @var Set<Relation> */
            $relations = Set::of();

            foreach ($package['require'] ?? [] as $require => $constraint) {
                $relations = Maybe::all(Name::maybe($require), Constraint::maybe($constraint))
                    ->map(Relation::of(...))
                    ->match(
                        static fn($relation) => ($relations)($relation),
                        static fn() => $relations,
                    );
            }

            $packages = Maybe::all(
                Name::maybe($package['name']),
                Version::maybe($package['version']),
            )
                ->map(static fn(Name $name, Version $version) => new Package(
                    $name,
                    $version,
                    Url::of('https://packagist.org/packages/'.$package['name']),
                    $relations,
                ))
                ->match(
                    static fn($package) => ($packages)($package),
                    static fn() => $packages,
                );
        }

        return $this->removeVirtualRelations($packages);
    }

    /**
     * @param Set<Package> $packages
     *
     * @return Set<Package>
     */
    private function removeVirtualRelations(Set $packages): Set
    {
        $installed = $packages->map(
            static fn(Package $package): Name => $package->name(),
        );

        return $packages->map(
            static fn(Package $package): Package => $package->keep($installed),
        );
    }
}
