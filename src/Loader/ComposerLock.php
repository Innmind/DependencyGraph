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
    Str,
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
            ->get(new FileName('composer.lock'))
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
            if (!$this->accepted($package['name'])) {
                continue;
            }

            /** @var Set<Relation> */
            $relations = Set::of();

            foreach ($package['require'] ?? [] as $require => $constraint) {
                if (!$this->accepted($require)) {
                    continue;
                }

                $relations = ($relations)(new Relation(
                    Name::of($require),
                    new Constraint($constraint),
                ));
            }

            $packages = ($packages)(new Package(
                Name::of($package['name']),
                new Version($package['version']),
                Url::of('https://packagist.org/packages/'.$package['name']),
                $relations,
            ));
        }

        return $this->removeVirtualRelations($packages);
    }

    private function accepted(string $name): bool
    {
        // do not accept extensions and php versions in the dependency graph
        return Str::of($name)->matches('~.+\/.+~');
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
