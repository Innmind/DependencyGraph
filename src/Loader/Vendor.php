<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Package as PackageModel,
    Vendor as VendorModel,
};
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Request,
    Method,
    ProtocolVersion,
};
use Innmind\Url\Url;
use Innmind\Validation\{
    Is,
    Constraint,
};
use Innmind\Json\Json;
use Innmind\Immutable\{
    Set,
    Map,
};

final class Vendor
{
    private Transport $fulfill;
    private Package $load;

    public function __construct(Transport $fulfill, Package $load)
    {
        $this->fulfill = $fulfill;
        $this->load = $load;
    }

    public function __invoke(VendorModel\Name $name): VendorModel
    {
        $url = "https://packagist.org/packages/list.json?vendor={$name->toString()}&fields[]=abandoned";
        /** @var Constraint<mixed, Set<string>> */
        $validate = Is::shape(
            'packages',
            Is::associativeArray(
                Is::string(),
                Is::shape('abandoned', Is::string()->or(Is::bool())),
            )
                ->map(static fn($packages) => $packages->filter(
                    static fn($_, $detail) => $detail['abandoned'] === false,
                ))
                ->map(static fn(Map $packages) => $packages->keys()),
        )->map(static fn($content): mixed => $content['packages']);

        $request = Request::of(
            Url::of($url),
            Method::get,
            ProtocolVersion::v20,
        );
        $packages = ($this->fulfill)($request)
            ->maybe()
            ->map(static fn($success) => $success->response()->body()->toString())
            ->map(Json::decode(...))
            ->flatMap(static fn($content) => $validate($content)->maybe())
            ->match(
                static fn($packages) => $packages,
                static fn() => throw new \RuntimeException,
            );

        // We chunk the packages by 100 to prevent keeping more than 100 http
        // responses in memory at a time. If unlimited the process may exhaust
        // the maximum number of opened files. This is the case when fetching
        // all Symfony dependencies (at the time of witing this comment it's
        // around 250 packages). On a macbook pro m1 the soft number of allowed
        // opened files is 256.
        // Obviously this limit could be raised but this means this tool can't
        // be used in some scenarii unless the user change the environment.
        return new VendorModel(
            $name,
            $packages
                ->unsorted()
                ->chunk(100)
                ->flatMap(
                    fn($chunk) => $chunk
                        ->map(
                            fn($name) => PackageModel\Name::maybe($name)
                                ->flatMap($this->load),
                        )
                        ->flatMap(static fn($package) => $package->toSequence()),
                )
                ->toSet(),
        );
    }
}
