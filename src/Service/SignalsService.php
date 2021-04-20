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
use App\Entity\DatasourceEntity;
use App\Entity\MetadataEntity;
use App\TimeSeries\Backend\BackendException;
use App\TimeSeries\Backend\GraphiteBackend;
use App\TimeSeries\Backend\InfluxBackend;
use App\TimeSeries\Backend\OutagesBackend;
use App\TimeSeries\TimeSeries;
use App\TimeSeries\TimeSeriesSet;
use App\Utils\QueryTime;
use Symfony\Component\Asset\Package;


class SignalsService
{

    /**
     * @var GraphiteBackend
     */
    private $graphiteBackend;
    /**
     * @var InfluxBackend
     */
    private $influxBackend;
    /**
     * @var OutagesBackend
     */
    private $outagesBackend;

    /**
     * All available down-sample steps an datasource can use.
     */
    const ALLOWED_STEPS = [
        60, 120, 300, 900, 1800, // minute-level [1, 2, 5, 15, 30]
        3600, 7200, 21600, 43200,  //hour-level [1, 2, 6, 12]
        86400, 172800, //day-level [1, 2]
        604800, 1209600, 2419200, //week-level [1, 2, 4]
        31536000, 63072000, 315360000, //year-level [1, 2, 10]
    ];

    /**
     * Calculate step based on the value of `from` and `until`.
     * @param QueryTime $from
     * @param QueryTime $until
     * @param int $maxPoints
     * @param DatasourceEntity $datasource
     * @return int
     */
    protected function calculateStep(QueryTime $from, QueryTime $until, ?int $maxPoints, int $nativeStep){

        // if no maxPoints specified, return the native step of the datasource
        if($maxPoints == null){
            return $nativeStep;
        }

        // find the smallest step that can get the number of data points below the specified $maxPoints
        $step = $nativeStep;
        $range = $until->getEpochTime() - $from->getEpochTime();
        foreach($this::ALLOWED_STEPS as $tmp_step){
            if($tmp_step<$nativeStep) {
                // ignore steps that are smaller than datasource's native step
                continue;
            }
            $step = $tmp_step;
            if($range/$tmp_step <= $maxPoints){
                break;
            }
        }
        return $step;
    }

    /**
     * Round down the `from` time based on the step.
     * @param $from
     * @param $step
     * @return QueryTime
     */
    private function roundDownFromTs($from, $step){
        $from_ts = $from->getEpochTime();
        $from_ts = floor($from_ts / $step) * $step;
        return new QueryTime($from_ts);
    }


    public function __construct(GraphiteBackend $graphiteBackend, InfluxBackend $influxBackend, OutagesBackend $outagesBackend) {
        $this->graphiteBackend = $graphiteBackend;
        $this->influxBackend = $influxBackend;
        $this->outagesBackend = $outagesBackend;
    }

    /**
     * Build graphite expression JSON object based on given $datasource.
     *
     * @param string $fqid
     * @param string $datasource_id
     * @return string
     */
    private function buildGraphiteExpression(string $fqid, string $datasource_id): string {
        $queryJsons = [
            "bgp" => "alias(bgp.prefix-visibility.$fqid.v4.visibility_threshold.min_50%_ff_peer_asns.visible_slash24_cnt,\"bgp\")",
            "ucsd-nt" => "alias(darknet.ucsd-nt.non-erratic.$fqid.uniq_src_ip,\"ucsd-nt\")",
            "ping-slash24" => "alias(sumSeries(keepLastValue(active.ping-slash24.$fqid.probers.team-1.caida-sdsc.*.up_slash24_cnt,1)),\"ping-slash24\")",
        ];
        return $queryJsons[$datasource_id];
    }

    /**
     * Build graphite expression JSON object based on given $datasource.
     *
     * @param string $fqid
     * @param string $datasource_id
     * @return string
     */
    private function buildMultiEntityGraphiteExpression(array $entities, string $datasource_id): string {
        // NOTE: for a list of fqids, there should only be one portion that are different.
        // It also must be the last field that are different from each other
        // Examples:
        // - asn: asn.133191
        // - country: geo.netacuity.NA.US
        // - region: geo.netacuity.NA.US.4437
        // - county: geo.netacuity.NA.US.4437.3103

        $common="";
        $unique=[];
        $entityType = $entities[0]->getType()->getType();

        foreach($entities as $entity) {
            $fqid = $entity->getAttribute("fqid");
            $fields = explode(".", $fqid);
            if($common == ""){
                $common = implode(".", array_slice($fields, 0, -1));
            }
            assert($entity->getCode() == end($fields));
            $unique[] = end($fields);
        }

        $fqid_combined = $common . ".{" . implode(",", $unique) . "}";

        $aliasIndex = [
            "asn" => 3,
            "country" => 5,
            "region" => 6,
        ];

        $queryJsons = [
            "bgp" => "aliasByNode(bgp.prefix-visibility.$fqid_combined.v4.visibility_threshold.min_50%_ff_peer_asns.visible_slash24_cnt, $aliasIndex[$entityType])",
            // NOTE: if see strange gaps in between bins, consider bring back keepLastValue function for ping-slash24
            "ping-slash24" => "aliasByNode(groupByNode(active.ping-slash24.$fqid_combined.probers.team-1.caida-sdsc.*.up_slash24_cnt,$aliasIndex[$entityType], 'sumSeries'), $aliasIndex[$entityType])",
        ];

        return $queryJsons[$datasource_id];
    }

    /**
     * Build influx query string based on the entity type and code.
     *
     * @param DatasourceEntity $datasourceEntity
     * @param MetadataEntity $entity
     * @param QueryTime $from
     * @param QueryTime $until
     * @param int $step
     * @return string influx query string
     * @throws BackendException
     */
    private function buildInfluxQuery(DatasourceEntity $datasourceEntity, MetadataEntity $entity,
                                      QueryTime $from, QueryTime $until, int $step): string {
        $entityType = $entity->getType()->getType();
        $entityCode = $entity->getCode();

        if($datasourceEntity->getDatasource() == "ucsd-nt"){
            $MEASUREMENT = [
                "country" => "geo_country",
                "region" => "geo_region",
                "county" => "geo_county",
                "asn" => "origin_asn",
            ];
            $WHERE = [
                "country" => "\"country_code\" = '%s' AND \"geo_db\" = 'netacuity'",
                "region"  => "\"region_code\" = '%s' AND \"geo_db\" = 'netacuity'",
                "county"  => "\"county_code\" = '%s' AND \"geo_db\" = 'netacuity'",
                "asn" => "\"asn\" = '%s'",
            ];

            $measurement = $MEASUREMENT[$entityType];
            $where = sprintf($WHERE[$entityType], $entityCode);
            $timeQuery = sprintf("time >= %ds AND time < %ds", $from->getEpochTime(), $until->getEpochTime());
            $step = sprintf("%ds",$step);
            return "q=SELECT mean(\"uniq_src_ip\") FROM \"$measurement\" WHERE (\"telescope\" = 'ucsd-nt' AND \"filter\" = 'non-erratic' AND $where) AND $timeQuery GROUP BY time($step) fill(null)";
        } else {
            throw new BackendException(
                sprintf("Datasource %s doesn't not currently support InfluxDB backend",
                    $datasourceEntity->getDatasource()
                ));
        }
    }

    /**
     * Build influx query string based on the entity type and code.
     *
     * @param DatasourceEntity $datasourceEntity
     * @param MetadataEntity $entity
     * @param QueryTime $from
     * @param QueryTime $until
     * @param int $step
     * @return string influx query string
     * @throws BackendException
     */
    private function buildMultiEntityInfluxQuery(string $datasource, array $entities,
                                      QueryTime $from, QueryTime $until, int $step): string {
        // make sure all entities has the same type
        $entityType = $entities[0]->getType()->getType();

        if($datasource != "ucsd-nt"){
            throw new BackendException(
                sprintf("Datasource %s doesn't not currently support InfluxDB backend",
                    $datasource
                ));
        }

        $MEASUREMENT = [
            "country" => "geo_country",
            "region" => "geo_region",
            "county" => "geo_county",
            "asn" => "origin_asn",
        ];

        $WHERE_CODE = [
            "country" => "country_code",
            "region"  => "region_code",
            "county"  => "county_code",
            "asn" => "asn",
        ];

        $or_statement = [];
        foreach($entities as $entity){
            $or_statement[] = sprintf("\"%s\"='%s'", $WHERE_CODE[$entityType], $entity->getCode());
        }
        if($entityType == "asn"){
            $where = sprintf("(%s)", implode(" OR ", $or_statement));
        } else {
            $where = sprintf("(%s) AND \"geo_db\" = 'netacuity'", implode(" OR ", $or_statement));
        }


        $measurement = $MEASUREMENT[$entityType];
        $group_by = "$measurement.$entityType";
        $timeQuery = sprintf("time >= %ds AND time < %ds", $from->getEpochTime(), $until->getEpochTime());
        $step = sprintf("%ds",$step);
        return "q=SELECT mean(\"uniq_src_ip\") FROM \"$measurement\" WHERE (\"telescope\" = 'ucsd-nt' AND \"filter\" = 'non-erratic' AND $where) AND $timeQuery GROUP BY time($step),$WHERE_CODE[$entityType]  fill(null)";
    }

    /**
     * @param $datasource_id
     * @return string
     * @throws BackendException
     */
    private function getInfluxDbName($datasource_id){
        $DBMAP = [
            "ucsd-nt" => "stardust_ucsdnt"
        ];
        if(!array_key_exists($datasource_id, $DBMAP)){
            throw new BackendException("Datasource $datasource_id doesn't not have corresponding InfluxDB database");
        }
        return $DBMAP[$datasource_id];
    }

    /**
     * Given a list of entities and a list of data sources to query, construct combined queries to improve overall
     * performance.
     *
     * @param QueryTime $from
     * @param QueryTime $until
     * @param array $entities
     * @param array $datasources
     * @param int|null $maxPoints
     * @return array
     */
    public function queryForAll(QueryTime $from, QueryTime $until, array $entities, array $datasources, ?int $maxPoints, ?bool $noinflux): array
    {
        $ts_array = [];

        $entityMap = [];
        foreach($entities as $entity){
            $entityMap[$entity->getCode()] = $entity;
        }

        foreach($datasources as $datasource){
            $backend = $datasource->getBackend();

            if($backend == "graphite"){
                $exp_json = $this->buildMultiEntityGraphiteExpression($entities, $datasource->getDatasource());
                $arr = $this->graphiteBackend->queryGraphite($from, $until, $exp_json, $maxPoints);
            } else if ($backend == "influx"){
                if(isset($noinflux) && $noinflux==true){
                    continue;
                }
                $step = $this->calculateStep($from, $until, $maxPoints, $datasource->getNativeStep());
                $from = $this->roundDownFromTs($from, $step);
                $influx_query = $this->buildMultiEntityInfluxQuery($datasource->getDatasource(), $entities, $from, $until, $step);
                $arr = $this->influxBackend->queryInflux($influx_query, $this->getInfluxDbName($datasource->getDatasource()));
            } else {
                throw new BackendException(
                    sprintf("invalid datasource %s", $datasource)
                );
            }

            // post-processing
            foreach(array_keys($arr) as $code){
                $ts = $arr[$code];
                $ts -> setMetadataEntity($entityMap[$code]);
                $ts -> setNativeStep($datasource->getNativeStep());
                $ts -> setDatasource($datasource->getDatasource());
                if(!array_key_exists($code, $ts_array)){
                    $ts_array[$code] = [];
                }
                $ts_array[$code][] = $ts;
            }
        }


        $ts_sets = [];
        foreach($entities as $entity){
            $ts_set = new TimeSeriesSet();
            $ts_set->setMetadataEntity($entity);

            $code = $entity->getCode();
            if(array_key_exists($code, $ts_array)){
                foreach($ts_array[$code] as $ts){
                    $ts_set->addOneSeries($ts);
                }
            }

            // add the timeseries set to result array
            $ts_sets[] = $ts_set;
        }
        return $ts_sets;
    }

    /**
     * @param QueryTime $from
     * @param QueryTime $until
     * @param MetadataEntity $entity
     * @param DatasourceEntity $datasource
     * @param int $maxPoints
     * @return TimeSeries
     * @throws BackendException
     */
    public function queryForTimeSeries(QueryTime $from, QueryTime $until, MetadataEntity $entity,
                                  DatasourceEntity $datasource, ?int $maxPoints): TimeSeries {
        $backend = $datasource->getBackend();

        if($backend == "graphite"){
            $exp_json = $this->buildGraphiteExpression($entity->getAttribute("fqid"), $datasource->getDatasource());
            $ts = array_values($this->graphiteBackend->queryGraphite($from, $until, $exp_json, $maxPoints))[0];
        } else if($backend=="influx"){
            // calculate step and round down starting time
            $step = $this->calculateStep($from, $until, $maxPoints, $datasource->getNativeStep());
            $from = $this->roundDownFromTs($from, $step);
            $influx_query = $this->buildInfluxQuery($datasource, $entity, $from, $until, $step);
            $ts = array_values($this->influxBackend->queryInflux($influx_query, $this->getInfluxDbName($datasource->getDatasource())))[0];
            $ts->setNativeStep($datasource->getNativeStep());
        } else {
            throw new BackendException(
                sprintf("invalid datasource %s", $datasource)
            );
        }
        $ts->setMetadataEntity($entity);
        $ts->setDatasource($datasource->getDatasource());
        return $ts;
    }

    /**
     * @param int $from
     * @param int $until
     * @param string $entityType
     * @param string $entityCode
     * @param $datasource
     * @param int|null $maxPoints
     * @return array
     */
    public function queryForEventsTimeSeries(int $from, int $until, string $entityType, string $entityCode, DatasourceEntity $datasource, ?int $maxPoints): array
    {
        if(null === $maxPoints){
            $maxPoints = 400;
        }
        return $this->outagesBackend->queryOutages($from, $until,$entityType, $entityCode, $maxPoints, $datasource->getDatasource());
    }
}
