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
use Innmind\Filesystem\Name as FileName;
use Innmind\Immutable\{
    Set,
    Str,
};
use function Innmind\Immutable\unwrap;

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
        $folder = $this->filesystem->mount($path);
        $composer = $folder->get(new FileName('composer.lock'));
        /** @var array{packages: list<array{name: string, version: string, require?: array<string, string>}>} */
        $lock = Json::decode($composer->content()->toString());

        return $this->denormalize($lock);
    }

    /**
     * @param array{packages: list<array{name: string, version: string, require?: array<string, string>}>} $composer
     *
     * @return Set<Package>
     */
    private function denormalize(array $composer): Set
    {
        /** @var Set<Package> */
        $packages = Set::of(Package::class);

        foreach ($composer['packages'] as $package) {
            if (!$this->accepted($package['name'])) {
                continue;
            }

            $relations = [];

            foreach ($package['require'] ?? [] as $require => $constraint) {
                if (!$this->accepted($require)) {
                    continue;
                }

                $relations[] = new Relation(
                    Name::of($require),
                    new Constraint($constraint),
                );
            }

            $packages = ($packages)(new Package(
                Name::of($package['name']),
                new Version($package['version']),
                Url::of('https://packagist.org/packages/'.$package['name']),
                ...$relations,
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
        $installed = $packages->mapTo(
            Name::class,
            static fn(Package $package): Name => $package->name(),
        );

        return $packages->map(
            static fn(Package $package): Package => $package->keep(
                ...unwrap($installed),
            ),
        );
    }
}
