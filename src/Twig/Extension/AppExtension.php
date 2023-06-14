<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\AppExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('excerpt', [AppExtensionRuntime::class, 'excerpt']),
        ];
    }

    public function getFunctions(): array
    {
        return [

        ];
    }
}
