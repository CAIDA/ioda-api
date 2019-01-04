<?php


namespace App\TimeSeries\Humanize\Provider;


abstract class AbstractHumanizeProvider
{
    /**
     * Attempt to find a human-readable name for the given node (with the given
     * FQID nodes)
     *
     * @param $fqid
     * @param $nodes
     * @param $finalNode
     *
     * @return string|null
     */
    public abstract function humanize(string $fqid, array &$nodes,
                                      string $finalNode): ?string;
}
