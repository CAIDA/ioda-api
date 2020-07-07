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
     * @param $from
     * @param $until
     * @param $entityType
     * @param $entityCode
     * @param $datasource
     * @param $limit
     * @param $page
     * @return OutagesAlert[]
     */
    public function findAlerts($from, $until, $entityType, $entityCode, $datasource, $limit, $page)
    {
        $alerts = $this->repo->findAlerts($from, $until, $entityType, $entityCode, $datasource, $limit, $page);

        foreach($alerts as &$alert){
            $type = $alert->getMetaType();
            $code = $alert->getMetaCode();
            $metas = $this->metadataService->lookup($type, $code);
            $alert->setEntity($metas[0]);
        }

        return $alerts;
    }
}
