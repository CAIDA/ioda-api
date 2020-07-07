<?php


namespace App\Outages;


use App\Entity\Outages\OutagesAlert;
use App\Repository\OutagesAlertsRepository;

class OutagesAlertsService
{
    /**
     * @var OutagesAlertsRepository
     */
    private $repo;

    public function __construct(OutagesAlertsRepository $outagesAlertsRepository)
    {
        $this->repo = $outagesAlertsRepository;
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
        return $this->repo->findAlerts($from, $until, $entityType, $entityCode, $datasource, $limit, $page);
    }
}
