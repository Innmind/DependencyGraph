<?php
declare(strict_types = 1);

namespace Innmind\DependencyGraph\Render;

use Innmind\DependencyGraph\Package;
use Innmind\Url\Url;

interface Locate
{
    public function __invoke(Package $package): Url;
}
