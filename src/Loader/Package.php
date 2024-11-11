<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\Package as Model;
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Request,
    Method,
    ProtocolVersion,
};
use Innmind\Url\Url;
use Innmind\Validation\Is;
use Innmind\Json\Json;
use Innmind\Immutable\{
    Set,
    Map,
    Sequence,
    Maybe,
    Predicate\Instance,
};
use Composer\Semver\{
    VersionParser,
    Semver,
};

final class Package
{
    private Transport $fulfill;

    public function __construct(Transport $fulfill)
    {
        $this->fulfill = $fulfill;
    }

    /**
     * @return Maybe<Model>
     */
    public function __invoke(Model\Name $name): Maybe
    {
        $request = Request::of(
            Url::of("https://packagist.org/packages/{$name->toString()}.json"),
            Method::get,
            ProtocolVersion::v20,
        );

        return ($this->fulfill)($request)
            ->maybe()
            ->map(static fn($success) => $success->response()->body()->toString())
            ->map(Json::decode(...))
            ->flatMap(fn($response) => $this->parse($response, $name));
    }

    /**
     * @return Maybe<Model>
     */
    private function parse(mixed $response, Model\Name $name): Maybe
    {
        /**
         * @psalm-suppress MixedArrayAccess
         * @psalm-suppress MixedArgument
         * @psalm-suppress MixedAssignment
         */
        $validate = Is::shape(
            'package',
            Is::shape('name', Is::string())
                ->with(
                    'repository',
                    Is::string()
                        ->map(static fn($value) => \rtrim($value, '/').'/')
                        ->map(Url::maybe(...))
                        ->and(Is::just()),
                )
                ->with(
                    'versions',
                    Is::associativeArray(
                        Is::string(),
                        Is::shape(
                            'version',
                            Is::string()
                                ->map(Model\Version::maybe(...))
                                ->and(Is::just()),
                        )
                            ->optional('abandoned', Is::string()->or(Is::bool()))
                            ->default('abandoned', false)
                            ->optional(
                                'require',
                                Is::associativeArray(
                                    Is::string(),
                                    Is::string(),
                                )
                                    ->map(static fn(Map $requires) => $requires->map(
                                        static fn(string $relation, string $constraint) => Maybe::all(
                                            Model\Name::maybe($relation),
                                            Model\Constraint::maybe($constraint),
                                        )
                                            ->map(Model\Relation::of(...)),
                                    ))
                                    ->map(
                                        static fn(Map $requires) => $requires
                                            ->values()
                                            ->flatMap(static fn(Maybe $relation) => $relation->toSequence())
                                            ->toSet(),
                                    ),
                            )
                            ->default('require', Set::of()),
                    )
                        ->map(static fn(Map $versions) => $versions->filter(
                            static fn(string $version) => VersionParser::parseStability($version) === 'stable',
                        ))
                        ->map(static function(Map $versions) {
                            $sorted = Sequence::of(...\array_values(
                                Semver::rsort(
                                    $versions->keys()->toList(),
                                ),
                            ));

                            return $sorted
                                ->first()
                                ->flatMap($versions->get(...));
                        })
                        ->and(Is::just()),
                )
                ->rename('versions', 'mostRecent')
                ->map(static fn($package) => new Model(
                    $name,
                    $package['mostRecent']['version'],
                    Url::of("https://packagist.org/packages/{$name->toString()}"),
                    $package['repository'],
                    $package['mostRecent']['require'],
                    $package['mostRecent']['abandoned'] !== false,
                )),
        )->map(static fn($content): mixed => $content['package']);

        return $validate($response)
            ->maybe()
            ->keep(Instance::of(Model::class));
    }
}
