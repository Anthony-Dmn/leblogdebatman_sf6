<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('excerpt', [$this, 'excerpt']), // Enregistrement du nouveau filtre auprès de Twig
        ];
    }

    /**
     * Filtre qui retournera la chaîne de texte donnée tronquée à "$nbWords" mots. Si trop petite le filtre retourne juste la chaîne sans y toucher
     */
    public function excerpt(string $text, int $nbWords): string
    {

        $arrayText = explode(' ', $text, ($nbWords + 1));

        if( count($arrayText) > $nbWords ){
            array_pop($arrayText);
            return implode(' ', $arrayText) . '...';
        }

        return $text;

    }
}
