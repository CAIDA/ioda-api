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


use App\Entity\OutagesEvent;
use App\Entity\OutagesSummary;
use App\Repository\OutagesAlertsRepository;

class OutagesEventsService
{
    /**
     * @var OutagesAlertsRepository
     */
    private $repo;

    /**
     * @var DatasourceService
     */
    private $datasourceService;

    public function __construct(OutagesAlertsRepository $outagesAlertsRepository, DatasourceService $datasourceService)
    {
        $this->repo = $outagesAlertsRepository;
        $this->datasourceService = $datasourceService;
    }

    private function cmpEvent($a, $b) {
        return $a['from'] - $b['from'];
    }

    private function cmpEventObj($a, $b) {
        return $a->getFrom() - $b->getFrom();
    }

    private function mergeEvents($events)
    {
        if (!count($events)) {
            return [];
        }
        $allEvents = [];
        foreach (array_keys($events) as $aId) {
            foreach ($events[$aId] as $event) {
                $fqid = $event['fqid'];
                $event['fqScores'] = [
                    $fqid => $event['score'],
                ];
                $event['score'] = 1; // used in multiplication below
                $allEvents[] = $event;
            }
        }
        if (!count($allEvents)) {
            return [];
        }

        // sort by from time
        usort($allEvents, [
            'App\Service\OutagesEventsService',
            'cmpEvent'
        ]);

        $merged = [];
        $merged[] = array_shift($allEvents);
        foreach ($allEvents as $event) {
            $last = array_pop($merged);
            if ($last['until'] < $event['from']) {
                // no overlap, just add as-is
                $merged[] = $last;
                $merged[] = $event;
            } else {
                if ($last['until'] < $event['until']) {
                    // need to extend last to include event
                    $last['until'] = $event['until'];
                }
                $eFqid = $event['fqid'];
                if (array_key_exists($eFqid, $last['fqScores'])) {
                    $last['fqScores'][$eFqid] += $event['fqScores'][$eFqid];
                } else {
                    $last['fqScores'][$eFqid] = $event['fqScores'][$eFqid];
                }
                $merged[] = $last;
            }
        }

        foreach ($merged as &$event) {
            foreach (array_keys($event['fqScores']) as $fqid) {
                $event['score'] *= $event['fqScores'][$fqid];
            }
        }

        return $merged;
    }

    private function groupAlertsByEntity($alerts){
        $res = [];
        foreach($alerts as $alert){
            $entity_id = $alert->getMetaType() . $alert->getMetaCode();
            if(!array_key_exists($entity_id, $res)){
                $res[$entity_id] = array();
            }
            $res[$entity_id][] = $alert;
        }
        return $res;
    }

    /**
     * @param $alerts
     * @param $includeAlerts
     * @param $format
     * @param $from
     * @param $until
     * @param $limit
     * @param int $page
     * @param string $orderBy
     * @return OutagesEvent[]
     */
    public function buildEventsSimple($alerts, $includeAlerts, $format, $from, $until, $limit, $page=0, $orderByAttr="time", $orderByOrder="asc"){
        $res = [];

        $eventmap = $this->buildEvents($alerts, $from, $until);
        foreach ($eventmap as $id => $events) {
            foreach($events as $event){
                $res[] = new OutagesEvent($event['from'], $event['until'],
                    $event['alerts'], $event['score'], $format, $includeAlerts, $event['X-Overlaps-Window']);
            }
        }

        if($orderByAttr=="score"){
            usort($res,
                function ($a, $b) {
                    return ($a->getScore() > $b->getScore() );
                }
            );
        } else if ($orderByAttr=="name") {
            usort($res,
                function ($a, $b) {
                    return strcmp($a->getEntity()->getName(), $b->getEntity()->getName() );
                }
            );
        } else if ($orderByAttr=="time") {
            usort($res,
                function ($a, $b) {
                    return ($a->getFrom() > $b->getFrom() );
                }
            );
        }

        if ($orderByOrder == "desc"){
            $res = array_reverse($res);
        }

        if ($limit) {
            $res = array_slice($res, $limit*$page, $limit);
        }

        return $res;
    }

    /**
     * @param array $events
     *
     * @return array
     */
    private function computeScores(&$events)
    {
        $res = [];
        foreach (array_keys($events) as $aId) {
            foreach ($events[$aId] as &$e) {
                $ds = $this->datasourceService->fqidToDatasourceName($e['fqid']);
                if (!array_key_exists($ds, $res)) {
                    $res[$ds] = 0;
                }
                $res[$ds] += $e['score'];
            }
        }

        $merged = $this->mergeEvents($events);
        $total = 0;
        foreach ($merged as $event) {
            $total += $event['score'];
        }
        $res["overall"] = $total;
        return $res;
    }

    /**
     * @param $alerts
     * @param $from
     * @param $until
     * @param $limit
     * @param int $page
     * @param string $orderByAttr
     * @param string $orderByOrder
     * @return OutagesEvent[]
     */
    public function buildEventsSummary($alerts, $from, $until, $limit, $page=0, $orderByAttr="score", $orderByOrder="asc"){
        if($orderByAttr=="time"){
            $orderByAttr="score";
        }
        $res = [];

        $alertGroups = $this->groupAlertsByEntity($alerts);
        foreach($alertGroups as $entity_id => $alerts){
            // all alerts here have the entity
            $eventmap = $this->buildEvents($alerts, $from, $until);
            $scores = $this->computeScores($eventmap);
            $res[] = new OutagesSummary($scores, $alerts[0]->getEntity());
        }


        if($orderByAttr=="score"){
            usort($res,
                function ($a, $b) {
                    return ($a->getScores()["overall"] > $b->getScores()["overall"] );
                }
            );
        } else if ($orderByAttr=="name") {
            usort($res,
                function ($a, $b) {
                    return strcmp($a->getEntity()->getName(), $b->getEntity()->getName() );
                }
            );
        }

        if($orderByOrder=="desc"){
            $res=array_reverse($res);
        }

        if ($limit) {
            $res = array_slice($res, $limit*$page, $limit);
        }
        return $res;
    }

    private function buildEvents($alerts, $from, $until, $orderBy="score"){
        // sort alerts by time
        // usort($alerts, ["App\Outages\OutagesEventsService","cmpAlert"]);

        # EVENT: "location" "start" "duration" "uncertainty" "status" "fraction" "score" "location_name" "overlaps_window"

        $events = []; // events[fqid] -> [complete events]
        $curEvents = []; // curEvents[fqid] -> ongoing event
        foreach ($alerts as &$a) {
            $fqid = $a->getFqid();
            $aId = $fqid . $a->getMetaType() . $a->getMetaCode();
            $level = $a->getLevel();
            $time = $a->getTime();
            $drop = (abs($a->getHistoryValue() -
                        $a->getValue()) /
                    max($a->getHistoryValue(), $a->getValue())) * 100;

            // are we tracking any events for this ID?
            if (!array_key_exists($aId, $events)) {
                $events[$aId] = [];
                $curEvents[$aId] = null;
            }
            $cE = $curEvents[$aId];
            if ($cE) {
                // we have an ongoing event for this ID, update it
                // two cases:
                // - normal alert => update score and finish event
                // - !normal alert => update score only

                // update the score based on the prevDrop and prevTime
                $interAlertMins = ($time - $cE['prevTime']) / 60;
                $cE['score'] += $cE['prevDrop'] * $interAlertMins;
                $cE['prevTime'] = $time;
                $cE['prevDrop'] = $drop;
                $cE['alerts'][] = $a;

                if ($level === "normal") {
                    $cE['until'] = $time;
                    unset($cE['prevTime']);
                    unset($cE['prevDrop']);
                    $curEvents[$aId] = null;
                    $events[$aId][] = $cE;
                }
            } else {
                // this is the first alert for this event
                if ($level == "normal") {
                    // we can't do anything with a normal-only event
                } else {
                    $curEvents[$aId] = [
                        'fqid' => $fqid,
                        'metaType' => $a->getMetaType(),
                        'metaCode' => $a->getMetaCode(),
                        'from' => $time,
                        'alerts' => [$a],
                        'X-Overlaps-Window' => false,
                        // until will be set at end
                        'score' => 0,

                        // temp values
                        'prevTime' => $time,
                        'prevDrop' => $drop,
                    ];
                }
            }
        }

        // finish any ongoing events by pretending we got a normal at windowEnd
        foreach (array_keys($curEvents) as $aId) {
            $cE = $curEvents[$aId];
            if ($cE) {
                $interAlertMins = ($until - $cE['prevTime']) / 60;
                $cE['score'] += $cE['prevDrop'] * $interAlertMins;
                unset($cE['prevTime']);
                unset($cE['prevDrop']);
                $cE['until'] = $until;
                $cE['X-Overlaps-Window'] = true;
                $events[$aId][] = $cE;
            }
        }

        return $events;
    }
}
