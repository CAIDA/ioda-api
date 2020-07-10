<?php


namespace App\Outages;


use App\Entity\Outages\OutagesAlert;
use App\Entity\Outages\OutagesEvent;
use App\MetadataEntities\MetadataEntitiesService;
use App\Repository\OutagesAlertsRepository;
use CAIDA\Charthouse\WatchtowerBundle\Entity\WatchtowerAlert;

class OutagesEventsService
{
    /**
     * @var OutagesAlertsRepository
     */
    private $repo;

    public function __construct(OutagesAlertsRepository $outagesAlertsRepository)
    {
        $this->repo = $outagesAlertsRepository;
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
            'App\Outages\OutagesEventsService',
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

    private function computeOverallScore(&$events)
    {
        $total = 0;
        foreach ($events as $event) {
            $total += $event['score'];
        }
        return $total;
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

    public function buildEventsSimple($alerts, $includeAlerts, $summarize, $format, $from, $until, $limit, $page=0){
        $res = [];

        if($summarize && $format=="ioda"){
            // TODO: rewrite the ioda format based on updated api-spec
            $groups = $this->groupAlertsByEntity($alerts);
            foreach($groups as $entity_id => $alerts){
                $eventmap = $this->buildEvents($alerts, $from, $until);
                foreach ($eventmap as $id => $events) {
                    $score = $this->computeOverallScore($events);
                    $res[] = new OutagesEvent(0, 0,
                        $alerts, $score, $format, $includeAlerts);
                }
            }
        } else {
            $eventmap = $this->buildEvents($alerts, $from, $until);
            foreach ($eventmap as $id => $events) {
                foreach($events as $event){
                    $res[] = new OutagesEvent($event['from'], $event['until'],
                        $event['alerts'], $event['score'], $format, $includeAlerts, $event['X-Overlaps-Window']);
                }
            }
        }

        usort($res, [ "App\Outages\OutagesEventsService","cmpEventObj"]);
        if ($limit) {
            $res = array_slice($res, $limit*$page, $limit);
        }

        return $res;
    }

    private function buildEvents($alerts, $from, $until){
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
