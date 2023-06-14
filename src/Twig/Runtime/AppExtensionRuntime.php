<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

class AppExtensionRuntime implements RuntimeExtensionInterface
{

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
