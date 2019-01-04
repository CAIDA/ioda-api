<?php


namespace App\TimeSeries\Humanize\Provider;


class DefaultHumanizeProvider extends AbstractHumanizeProvider
{

    public function humanize(string $fqid, array &$nodes,
                             string $finalNode): ?string
    {
        return ucwords($finalNode);
    }

}
