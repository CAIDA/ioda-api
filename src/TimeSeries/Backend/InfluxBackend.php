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

namespace App\TimeSeries\Backend;


use App\Entity\Ioda\DatasourceEntity;
use App\Entity\Ioda\MetadataEntity;
use App\TimeSeries\TimeSeries;
use App\Utils\QueryTime;
use DateTime;

class InfluxBackend
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
        } else if($entityType == "county"){
            $template = "q=SELECT mean(\"uniq_src_ip\") FROM \"geo_county\" WHERE (\"telescope\" = 'ucsd-nt' AND \"country_code\" = '%s' AND \"filter\" = 'non-erratic' AND \"geo_db\" = 'netacuity') AND %s GROUP BY time(%ds) fill(null)";
        } else if($entityType == "asn"){
            $template = "q=SELECT mean(\"uniq_src_ip\")  FROM \"origin_asn\" WHERE (\"telescope\" = 'ucsd-nt' AND  \"filter\" = 'non-erratic' AND \"asn\" = '%s') AND %s GROUP BY time(%ds) fill(null)";
        } else {
            throw new \InvalidArgumentException("Unsupported metadata entity type: $entityType");
        }

        // convert epoch time to nanoseconds
        $timeQuery = sprintf("time >= %ds AND time < %ds", $from->getEpochTime(), $until->getEpochTime());

        $query = sprintf($template, $entityCode, $timeQuery, $step);
        return $query;
    }

    private function sendQuery($query): array {
        $secret = getenv("INFLUXDB_SECRET");
        if(!$secret){
            throw new BackendException("Missing INFLUXDB_SECRET environment variable");
        }

        // create curl resource
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://explore.stardust.caida.org/api/datasources/proxy/1/query?db=stardust_ucsdnt&epoch=ms");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            "Authorization: Bearer $secret"
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

    /**
     * Calculate step based on the value of `from` and `until`.
     * @param QueryTime $from
     * @param QueryTime $until
     * @param int $maxPoints
     * @param DatasourceEntity $datasource
     * @return int
     */
    private function calculateStep(QueryTime $from, QueryTime $until, int $maxPoints, DatasourceEntity $datasource){

        // getSteps() function returns all allowed steps for this data source, sorted by increasing order
        $sorted_steps = $datasource->getSteps();
        // get the minimum step defined by the datasource as the default (starting) step
        $step = $sorted_steps[0];

        // if no maxPoints specified, return the minimum step
        if($maxPoints == null){
            return $step;
        }

        // find the smallest step that can get the number of data points below the specified $maxPoints
        $range = $until->getEpochTime() - $from->getEpochTime();
        foreach($sorted_steps as $tmp_step){
            $step = $tmp_step;
            if($range/$tmp_step <= $maxPoints){
                break;
            }
        }
        return $step;
    }

    /**
     * Round up the `from` time based on the step.
     * @param $from
     * @param $step
     * @return QueryTime
     */
    private function roundUpFrom($from, $step){
        $from_ts = $from->getEpochTime();
        $from_ts = floor($from_ts / $step) * $step;
        return new QueryTime($from_ts);
    }

    /**
     * process JSON response get from influxdb instance
     */
    private function processResponseJson($responseJson): TimeSeries {
        // sanity check json responses
        if(
            !in_array("results", array_keys($responseJson)) ||
            count($responseJson['results'])!=1 ||
            !array_key_exists("series", $responseJson['results'][0]) ||
            count($responseJson['results'][0]['series'])!=1
        ){
            $message = "InfluxDB backend failure";
            $message .= in_array("message", array_keys($responseJson))?
                sprintf(": %s",$responseJson["message"]): "" ;
            throw new BackendException($message);
        }
        $data = $responseJson['results'][0]['series'][0];

        $from = new DateTime();
        $until = new DateTime();
        $step = 0;
        $prev_ts = 0;

        $values = [];
        // retrieve values and calculate steps
        foreach($data['values'] as $value_pair){
            $cur_ts = $value_pair[0]/1000;  // influx returns timestamp in miliseconds
            // save the actual datapoint value to array
            $values[] = $value_pair[1];

            // use the first two iterations to calculate the step from the returned data
            if($step==0){
                if($prev_ts==0){
                    $prev_ts = $cur_ts;
                } else {
                    $step = $cur_ts - $prev_ts;
                }
            }
        }

        $from->setTimestamp($data['values'][0][0]/1000);
        $until->setTimestamp(end($data['values'])[0]/1000 + $step);

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

    /**
     * Influx service main entry point.
     *
     * @param DatasourceEntity $datasource
     * @param MetadataEntity $entity
     * @param QueryTime $from
     * @param QueryTime $until
     * @param int|null $maxPoints
     * @return TimeSeries
     * @throws \App\TimeSeries\Backend\BackendException
     */
    public function getInfluxDataPoints(DatasourceEntity $datasource, MetadataEntity $entity, QueryTime $from, QueryTime $until, ?int $maxPoints): TimeSeries
    {
        // assign default max points to avoid unnecessary expensive queries
        if($maxPoints == null){
            $maxPoints = TimeSeries::DEFAULT_MAX_POINTS;
        }

        // calculate query step
        $step = $this->calculateStep($from, $until, $maxPoints, $datasource);
        // round up $from based on the$ step
        $from = $this->roundUpFrom($from, $step);

        // build the actual influx query
        $query =  $this->buildInfluxQuery($entity->getType()->getType(), $entity->getCode(), $from, $until, $step);

        // send query and process response
        $res = $this->sendQuery($query);
        $series = $this->processResponseJson($res);
        // attach the metadata entity to the time series response
        $series->setMetadataEntity($entity);

        return $series;
    }
}
