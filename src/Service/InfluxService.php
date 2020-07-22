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
use App\TimeSeries\TimeSeries;
use App\Utils\QueryTime;
use DateTime;

class InfluxService
{

    /**
     * Build influx query string based on the entity type and code.
     *
     * @param string $entityType metadata entity type
     * @param string $entityCode metadata entity code
     * @param QueryTime $from
     * @param QueryTime $until
     * @return string influx query string
     */
    private function buildInfluxQuery(string $entityType, string $entityCode, QueryTime $from, QueryTime $until, int $step): string {
        // create query template
        if($entityType == "country"){
            $template = "q=SELECT mean(\"uniq_src_ip\") FROM \"geo_country\" WHERE (\"telescope\" = 'ucsd-nt' AND \"country_code\" = '%s' AND \"filter\" = 'non-erratic' AND \"geo_db\" = 'netacuity') AND %s GROUP BY time(%ds) fill(null)";
        } else if($entityType == "region"){
            $template = "q=SELECT mean(\"uniq_src_ip\") FROM \"geo_region\" WHERE (\"telescope\" = 'ucsd-nt' AND \"country_code\" = '%s' AND \"filter\" = 'non-erratic' AND \"geo_db\" = 'netacuity') AND %s GROUP BY time(%ds) fill(null)";
        } else if($entityType == "asn"){
            $template = "q=SELECT mean(\"uniq_src_ip\")  FROM \"origin_asn\" WHERE (\"telescope\" = 'ucsd-nt' AND  \"filter\" = 'non-erratic' AND \"asn\" = '%s') AND %s GROUP BY time(%ds) fill(null)";
        } else {
            throw new \InvalidArgumentException("Unsupported metadata entity type: $entityType");
        }

        // convert epoch time to nanoseconds
        $timeQuery = sprintf("time >= %ds AND time <= %ds", $from->getEpochTime(), $until->getEpochTime());

        $query = sprintf($template, $entityCode, $timeQuery, $step);
        return $query;
    }

    private function sendQuery($query): array {
        // create curl resource
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://explore.stardust.caida.org/api/datasources/proxy/1/query?db=stardust_ucsdnt&epoch=ms");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer eyJrIjoiMmdBN21ZQVdwY2Uzcko5bnZkS2libm40V3VoN0NBdUMiLCJuIjoiaW9kYS1hcGktdGVzdCIsImlkIjoxfQ=='
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $output = curl_exec($ch);
        // close curl resource to free up system resources
        curl_close($ch);
        return json_decode($output, true);
    }

    const DEFAULT_STEP = 60;

    private function calculateStep($from, $until, $maxPoints){
        if($maxPoints == null){
            return InfluxService::DEFAULT_STEP;
        }
        $range = $until->getEpochTime() - $from->getEpochTime();
        $step = InfluxService::DEFAULT_STEP;
        foreach(TimeSeries::ALLOWED_STEPS as $tmp_step){
            $step = $tmp_step;
            if($range/$tmp_step <= $maxPoints){
                break;
            }
        }

        return $step;
    }

    /**
     * process JSON response get from influxdb instance
     */
    private function processResponseJson($responseJson): TimeSeries {
        if(
            count($responseJson['results'])!=1 ||
            !array_key_exists("series", $responseJson['results'][0]) ||
            count($responseJson['results'][0]['series'])!=1
        ){
            throw new \InvalidArgumentException(
                sprintf("cannot find corresponding influx data entity")
            );
        }
        $data = $responseJson['results'][0]['series'][0];

        $from = new DateTime();
        $until = new DateTime();
        $from->setTimestamp($data['values'][0][0]/1000);
        $until->setTimestamp(end($data['values'])[0]/1000);
        $step = 0;
        $prev_ts = 0;

        $values = [];
        foreach($data['values'] as $value_pair){
            // use the first two iterations to calculate the step from the returned data
            if($step==0){
                $cur_ts = $value_pair[0]/1000;  // influx returns timestamp in miliseconds
                if($prev_ts==0){
                    $prev_ts = $cur_ts;
                } else {
                    $step = $cur_ts - $prev_ts;
                }
            }
            // save the actual datapoint value to array
            $values[] = $value_pair[1];
        }

        // create new TimeSeries object accordingly
        $newSeries = new TimeSeries();
        $newSeries->setDatasource('ucsd-nt');
        $newSeries->setFrom($from);
        $newSeries->setUntil($until);
        $newSeries->setStep($step);
        $newSeries->setNativeStep($step);
        $newSeries->setValues($values);
        return $newSeries;
    }

    public function getInfluxDataPoints(MetadataEntity $entity, QueryTime $from, QueryTime $until, ?int $maxPoints): TimeSeries
    {
        // build query
        $step = $this->calculateStep($from, $until, $maxPoints);
        $query =  $this->buildInfluxQuery($entity->getType()->getType(), $entity->getCode(), $from, $until, $step);
        $res = $this->sendQuery($query);
        $series = $this->processResponseJson($res);
        $series->setMetadataEntity($entity);
        return $series;
    }
}
