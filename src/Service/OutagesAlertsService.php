<?php
/**
 * This software is Copyright (c) 2013 The Regents of the University of
 * California. All Rights Reserved. Permission to copy, modify, and distribute this
 * software and its documentation for academic research and education purposes,
 * without fee, and without a written agreement is hereby granted, provided that
 * the above copyright notice, this paragraph and the following three paragraphs
 * appear in all copies. Permission to make use of this software for other than
 * academic research and education purposes may be obtained by contacting:
 *
 * Office of Innovation and Commercialization
 * 9500 Gilman Drive, Mail Code 0910
 * University of California
 * La Jolla, CA 92093-0910
 * (858) 534-5815
 * invent@ucsd.edu
 *
 * This software program and documentation are copyrighted by The Regents of the
 * University of California. The software program and documentation are supplied
 * "as is", without any accompanying services from The Regents. The Regents does
 * not warrant that the operation of the program will be uninterrupted or
 * error-free. The end-user understands that the program was developed for research
 * purposes and is advised not to rely exclusively on the program for any reason.
 *
 * IN NO EVENT SHALL THE UNIVERSITY OF CALIFORNIA BE LIABLE TO ANY PARTY FOR
 * DIRECT, INDIRECT, SPECIAL, INCIDENTAL, OR CONSEQUENTIAL DAMAGES, INCLUDING LOST
 * PROFITS, ARISING OUT OF THE USE OF THIS SOFTWARE AND ITS DOCUMENTATION, EVEN IF
 * THE UNIVERSITY OF CALIFORNIA HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE. THE UNIVERSITY OF CALIFORNIA SPECIFICALLY DISCLAIMS ANY WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE. THE SOFTWARE PROVIDED HEREUNDER IS ON AN "AS
 * IS" BASIS, AND THE UNIVERSITY OF CALIFORNIA HAS NO OBLIGATIONS TO PROVIDE
 * MAINTENANCE, SUPPORT, UPDATES, ENHANCEMENTS, OR MODIFICATIONS.
 */

namespace App\Service;


use App\Entity\Ioda\MetadataEntity;
use App\Entity\Ioda\MetadataEntityType;
use App\Entity\Outages\OutagesAlert;
use App\Repository\OutagesAlertsRepository;

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

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    public function __construct(OutagesAlertsRepository $outagesAlertsRepository,
                                MetadataEntitiesService $metadataEntitiesService,
                                DatasourceService $datasourceService)
    {
        $this->repo = $outagesAlertsRepository;
        $this->metadataService = $metadataEntitiesService;
        $this->datasourceService = $datasourceService;
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
     * @param null $limit
     * @param int $page
     * @param bool $lookup_entity
     * @return OutagesAlert[]
     */
    public function findAlerts($from, $until, $entityType, $entityCode, $datasource, $limit=null, $page=0, $lookup_entity=true)
    {
        // find alerts, already sorted by time
        $alerts = $this->repo->findAlerts($from, $until, $entityType, $entityCode, $datasource);

        // squash alerts
        $alerts = $this->squashAlerts($alerts);

        if ($limit) {
            // paginate
            $alerts = array_slice($alerts, $limit*$page, $limit);
        }

        $res = [];
        foreach($alerts as &$alert){
            // TODO: eventually, find a way to let doctrine to the work.
            $type = $alert->getMetaType();
            $code = $alert->getMetaCode();
            if($lookup_entity){
                $metas = $this->metadataService->search($type, $code);
                if(count($metas)!=1){
                    continue;
                }
                $alert->setEntity($metas[0]);
            } else {
                $entity = new MetadataEntity();
                $entity_type = new MetadataEntityType();
                $entity_type->setType($type);
                $entity->setType($entity_type);
                $entity->setCode($code);
                // $entity->setName("unknown");
                $alert->setEntity($entity);
            }
            $alert->setDatasource($this->datasourceService->fqidToDatasourceName($alert->getFqid()));
            $res[] = $alert;
        }

        return $res;
    }
}
