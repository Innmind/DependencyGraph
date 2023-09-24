<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Loader;

use Innmind\DependencyGraph\{
    Package as PackageModel,
    Vendor as VendorModel,
};
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method,
    ProtocolVersion,
};
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Immutable\{
    Str,
    Set,
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

        $request = new Request(
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

        return new VendorModel(
            $name,
            $packages
                ->map(fn($name) => PackageModel\Name::maybe($name)->flatMap(
                    $this->load,
                ))
                ->flatMap(
                    static fn($package) => $package
                        ->toSequence()
                        ->toSet(),
                ),
        );
    }
}
