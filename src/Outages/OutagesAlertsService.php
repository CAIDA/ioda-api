<?php


namespace App\Outages;


use App\Entity\Outages\OutagesAlert;
use App\MetadataEntities\MetadataEntitiesService;
use App\Repository\OutagesAlertsRepository;
use CAIDA\Charthouse\WatchtowerBundle\Entity\WatchtowerAlert;

class OutagesAlertsService
{
    /**
     * @var OutagesAlertsRepository
     */
    private $repo;

    /**
     * @var MetadataEntitiesService
     */
    private $metadataService;

    public function __construct(OutagesAlertsRepository $outagesAlertsRepository, MetadataEntitiesService $metadataEntitiesService)
    {
        $this->repo = $outagesAlertsRepository;
        $this->metadataService = $metadataEntitiesService;
    }

    /**
     * @param OutagesAlert[] $alerts
     *
     * @return OutagesAlert[]
     *
     * This function takes a series of alerts, groups them by fqid, meta
     * type/code, and then coalesces adjacent alerts based on the following
     * criteria:
     *  - if the alert level is the same as the previous alert, drop
     *  - if the alert level is a transition from "critical" -> !"normal", drop
     * That is, the only allowed transitions are:
     *   N->W[->C], N->C, C->N
     */
    private function squashAlerts(&$alerts)
    {
        $squashed = [];
        $currentLevel = [];

        foreach ($alerts as &$alert) {
            $alertId = $alert->getFqid() . $alert->getMetaType() . $alert->getMetaCode();
            $level = $alert->getLevel();
            if (!array_key_exists($alertId, $currentLevel)) {
                $currentLevel[$alertId] = $level;
                $squashed[] = $alert;
                continue;
            }
            $cl = $currentLevel[$alertId];
            if (($cl == "critical" && $level == "normal") ||
                ($cl != "critical" && $cl != $level)) {
                // N->W[->C], N->C, C->N
                $currentLevel[$alertId] = $level;
                $squashed[] = $alert;
                continue;
            }
        }

        return $squashed;
    }

    /**
     * @param $from
     * @param $until
     * @param $entityType
     * @param $entityCode
     * @param $datasource
     * @param $limit
     * @param $page
     * @return OutagesAlert[]
     */
    public function findAlerts($from, $until, $entityType, $entityCode, $datasource, $limit=null, $page=0)
    {
        // find alerts, already sorted by time
        $alerts = $this->repo->findAlerts($from, $until, $entityType, $entityCode, $datasource);

        // squash alerts
        $alerts = $this->squashAlerts($alerts);

        $res = [];
        foreach($alerts as &$alert){
            // TODO: eventually, find a way to let doctrine to the work.
            $type = $alert->getMetaType();
            $code = $alert->getMetaCode();
            $metas = $this->metadataService->lookup($type, $code);
            if(count($metas)==0){
                continue;
            }
            $alert->setEntity($metas[0]);

            // map datasources to short names
            $datasource = $alert->getFqid();
            if(strpos($datasource,"bgp")!==false){
                $datasource = "bgp";
            } elseif (strpos($datasource,"ucsd-nt")!==false){
                $datasource = "ucsd-nt";
            } elseif (strpos($datasource,"ping-slash24")!==false){
                $datasource = "ping-slash24";
            }
            $alert->setDatasource($datasource);
            $res[] = $alert;
        }

        if ($limit) {
            $res = array_slice($res, $limit*$page, $limit);
        }

        return $res;
    }
}
