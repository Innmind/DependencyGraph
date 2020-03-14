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

        return $this->denormalize(Json::decode($composer->content()->toString()));
    }

    private function denormalize(array $composer): Set
    {
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
                    new Constraint($constraint)
                );
            }

            $packages = $packages->add(new Package(
                Name::of($package['name']),
                new Version($package['version']),
                Url::of('https://packagist.org/packages/'.$package['name']),
                ...$relations
            ));
        }

        return $this->removeVirtualRelations($packages);
    }

    private function accepted(string $name): bool
    {
        // do not accept extensions and php versions in the dependency graph
        return Str::of($name)->matches('~.+\/.+~');
    }

    private function removeVirtualRelations(Set $packages): Set
    {
        $installed = $packages->reduce(
            Set::of(Name::class),
            static function(Set $installed, Package $package): Set {
                return $installed->add($package->name());
            }
        );

        return $packages->map(static function(Package $package) use ($installed): Package {
            return $package->keep(...unwrap($installed));
        });
    }
}
