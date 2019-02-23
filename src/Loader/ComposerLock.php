<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Package,
    Package\Name,
    Package\Version,
    Package\Relation,
};
use Innmind\Url\{
    PathInterface,
    Url,
};
use Innmind\Json\Json;
use Innmind\OperatingSystem\Filesystem;
use Innmind\Immutable\{
    SetInterface,
    Set,
    Str,
};

final class ComposerLock
{
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @return SetInterface<Package>
     */
    public function __invoke(PathInterface $path): SetInterface
    {
        $folder = $this->filesystem->mount($path);
        $composer = $folder->get('composer.lock');

        return $this->denormalize(Json::decode((string) $composer->content()));
    }

    private function denormalize(array $composer): SetInterface
    {
        $packages = Set::of(Package::class);

        foreach ($composer['packages'] as $package) {
            if (!$this->accepted($package['name'])) {
                continue;
            }

            $relations = [];

            foreach ($package['require'] ?? [] as $require => $_) {
                if (!$this->accepted($require)) {
                    continue;
                }

                $relations[] = new Relation(Name::of($require));
            }

            $packages = $packages->add(new Package(
                Name::of($package['name']),
                new Version($package['version']),
                Url::fromString('https://packagist.org/packages/'.$package['name']),
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

    private function removeVirtualRelations(SetInterface $packages): SetInterface
    {
        $installed = $packages->reduce(
            Set::of(Name::class),
            static function(SetInterface $installed, Package $package): SetInterface {
                return $installed->add($package->name());
            }
        );

        return $packages->map(static function(Package $package) use ($installed): Package {
            return $package->keep(...$installed);
        });
    }
}
