<?php


namespace App\SymUrl;


use App\Entity\SymUrl;

class SymUrlFactory
{
    public static function createOrGet(string $url, ?string $shortTag): SymUrl
    {
        $sym = new SymUrl();
        $sym->setLongUrl("http://www.caida.org/");
        $sym->setShortUrl($shortTag ? $shortTag : 'test-xx');
        return $sym;
    }

    public static function getExisting(string $shortTag,
                                       bool $updateStats = false): ?SymUrl
    {
        $sym = new SymUrl();
        $sym->setLongUrl("http://www.caida.org/");
        $sym->setShortUrl('test-existing');
        return $sym;
    }
}
