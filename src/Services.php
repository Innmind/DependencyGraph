<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph;

use Innmind\OperatingSystem\Filesystem;
use Innmind\Server\Control\Server\Processes;
use Innmind\DI\Service;

/**
 * @implements Service<object>
 */
enum Services implements Service
{
    case render;
    case save;
    case display;
    case package;
    case vendor;
    case filesystem;
    case processes;

    /**
     * @return Service<Render>
     */
    public static function render(): Service
    {
        /** @var Service<Render> */
        return self::render;
    }

    /**
     * @return Service<Save>
     */
    public static function save(): Service
    {
        /** @var Service<Save> */
        return self::save;
    }

    /**
     * @return Service<Display>
     */
    public static function display(): Service
    {
        /** @var Service<Display> */
        return self::display;
    }

    /**
     * @return Service<Loader\Package>
     */
    public static function package(): Service
    {
        /** @var Service<Loader\Package> */
        return self::package;
    }

    /**
     * @return Service<Loader\Vendor>
     */
    public static function vendor(): Service
    {
        /** @var Service<Loader\Vendor> */
        return self::vendor;
    }

    /**
     * @return Service<Filesystem>
     */
    public static function filesystem(): Service
    {
        /** @var Service<Filesystem> */
        return self::filesystem;
    }

    /**
     * @return Service<Processes>
     */
    public static function processes(): Service
    {
        /** @var Service<Processes> */
        return self::processes;
    }
}
