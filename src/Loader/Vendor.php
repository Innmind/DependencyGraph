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
        $url = "https://packagist.org/search.json?q={$name->toString()}/";
        $results = [];

        do {
            $request = new Request(
                Url::of($url),
                Method::get,
                ProtocolVersion::v20,
            );
            $response = ($this->fulfill)($request)->match(
                static fn($success) => $success->response(),
                static fn() => throw new \RuntimeException,
            );
            /** @var array{results: list<array{name: string, description: string, url: string, repository: string, virtual?: bool}>, total: int, next?: string} */
            $content = Json::decode($response->body()->toString());
            $results = \array_merge($results, $content['results']);
            $url = $content['next'] ?? null;
        } while (!\is_null($url));

        /** @var Set<PackageModel> */
        $packages = Set::of();

        foreach ($results as $result) {
            if (!Str::of($result['name'])->matches("~^{$name->toString()}/~")) {
                continue;
            }

            if ($result['virtual'] ?? false === true) {
                continue;
            }

            $packages = PackageModel\Name::maybe($result['name'])
                ->flatMap($this->load)
                ->filter(static fn($package) => !$package->abandoned())
                ->match(
                    static fn($package) => ($packages)($package),
                    static fn() => $packages,
                );
        }

        return new VendorModel($name, $packages);
    }
}
