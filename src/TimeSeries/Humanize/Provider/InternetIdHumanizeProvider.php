<?php


namespace App\TimeSeries\Humanize\Provider;


class InternetIdHumanizeProvider extends AbstractHumanizeProvider
{

    public function humanize(string $fqid, array &$nodes,
                             string $finalNode): ?string
    {
        // we require the node start with __
        if (strncmp($finalNode, "__", 2) != 0) {
            return null;
        }
        // check if this is an IP
        if (substr($finalNode, 0, 5) == "__IP_") {
            return str_replace('-', '.', substr($finalNode, 5));
        }
        // check if this is a prefix
        if (substr($finalNode, 0, 6) != "__PFX_") {
            return null;
        }
        $ip_len = explode('_', substr($finalNode, 6));
        if (count($ip_len) == 2) {
            return str_replace('-', '.', $ip_len[0]) . '/' . $ip_len[1];
        }
        // not an IP or a prefix
        return null;
    }

}
