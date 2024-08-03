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
use Innmind\Json\Json;
use Innmind\Immutable\Set;

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

        $request = Request::of(
            Url::of($url),
            Method::get,
            ProtocolVersion::v20,
        );
        $response = ($this->fulfill)($request)->match(
            static fn($success) => $success->response(),
            static fn() => throw new \RuntimeException,
        );
        /** @var array{packages: array<string, array{abandoned: bool|string}>} */
        $content = Json::decode($response->body()->toString());

        /** @var Set<string> */
        $packages = Set::of();

        foreach ($content['packages'] as $packageName => $detail) {
            if ($detail['abandoned'] !== false) {
                continue;
            }

            $packages = ($packages)($packageName);
        }

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
