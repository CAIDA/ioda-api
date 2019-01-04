<?php


namespace App\TimeSeries\Humanize;


use App\Expression\PathExpression;
use App\TimeSeries\Humanize\Provider\AbstractHumanizeProvider;
use App\TimeSeries\Humanize\Provider\DefaultHumanizeProvider;
use App\TimeSeries\Humanize\Provider\DirectHumanizeProvider;
use App\TimeSeries\Humanize\Provider\GeoHumanizeProvider;
use App\TimeSeries\Humanize\Provider\InternetIdHumanizeProvider;

class Humanizer
{
    private $providers = null;

    public function __construct()
    {
        $this->providers = [
            new DirectHumanizeProvider(),
            new GeoHumanizeProvider(),
            new InternetIdHumanizeProvider(),
            new DefaultHumanizeProvider(),
        ];
    }

    /**
     * Attempt to find a human-readable name for the given nodes/finalnode
     *
     * @param string $fqid
     * @param string[] $nodes
     * @param string $finalNode
     *
     * @return string|null
     */
    public
    function humanize(string $fqid, array &$nodes, string $finalNode): ?string
    {
        if (!isset($finalNode)) {
            return null;
        }
        /** @var AbstractHumanizeProvider $provider */
        foreach ($this->providers as $provider) {
            if (($human = $provider->humanize($fqid, $nodes, $finalNode)) !== null) {
                return $human;
            }
        }
        return null; // should never happen
    }

    /**
     * Attempt to find a human-readable name for the given FQID
     *
     * @param string|array $fqid
     * @param bool $asArray
     *
     * @return string|array
     */
    public
    function humanizeFqid($fqid, bool $asArray = false)
    {
        if (!is_array($fqid)) {
            $fqid = explode(PathExpression::SEPARATOR, $fqid);
        }
        $humanNodes = [];
        $partialFqid = '';
        $partialNodes = [];
        foreach ($fqid as $node) {
            $partialFqid .= $node . PathExpression::SEPARATOR;
            $partialNodes[] = $node;
            $humanNodes[] = $this->humanize($partialFqid, $partialNodes, $node);
        }
        if ($asArray) {
            return $humanNodes;
        } else {
            return PathExpression::nodesToString($humanNodes, true);
        }
    }
}
