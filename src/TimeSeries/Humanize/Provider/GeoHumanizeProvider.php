<?php


namespace App\TimeSeries\Humanize\Provider;


class GeoHumanizeProvider extends AbstractHumanizeProvider
{
    public function humanize(string $fqid, array &$nodes,
                             string $finalNode): ?string
    {
        // short-cut to avoid searching all nodes when there is no geo
        if (strpos($fqid, "geo") === false) {
            return null;
        }
        $ptr = GeoHumanizeProviderTable::getTable();
        $inTree = false;
        foreach ($nodes as $node) {
            if (array_key_exists($node, $ptr)) {
                $inTree = true;
                $ptr = &$ptr[$node];
            } else {
                if ($inTree) {
                    $ptr = null;
                    break;
                }
            }
        }
        if ($inTree && $ptr && array_key_exists('__NAME', $ptr)) {
            return $ptr['__NAME'];
        }
        return null;
    }
}
