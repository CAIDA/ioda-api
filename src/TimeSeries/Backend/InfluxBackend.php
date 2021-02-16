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


use App\TimeSeries\TimeSeries;
use DateTime;

class InfluxBackend
{

    /**
     * @param $query
     * @param $db_name influx db name
     * @return array
     * @throws BackendException
     */
    private function sendQuery(string $query, string $db_name): array {
        // retrive environment variables for inlfuxdb connection
        $secret = getenv("INFLUXDB_SECRET");
        $influx_uri = getenv("INFLUXDB_API");
        if(!$secret){
            throw new BackendException("Missing INFLUXDB_SECRET environment variable");
        }
        if(!$influx_uri){
            throw new BackendException("Missing INFLUXDB_API environment variable");
        }

        // create curl resource
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$influx_uri/query?db=$db_name&epoch=ms");
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
     * process JSON response get from influxdb instance
     * @param array $responseJson
     * @return TimeSeries
     * @throws BackendException
     */
    private function processResponseJson(array $responseJson): TimeSeries {
        // sanity check json responses
        if(
            !in_array("results", array_keys($responseJson)) ||
            count($responseJson['results'])!=1 ||
            !array_key_exists("series", $responseJson['results'][0]) ||
            count($responseJson['results'][0]['series'])!=1
        ){
            $message = "InfluxDB backend failure";
            if( !array_key_exists("series", $responseJson['results'][0]) ||
            count($responseJson['results'][0]['series'])!=1){
                $message .= sprintf(": no data in range") ;
            } else {
                $message .= in_array("message", array_keys($responseJson))?
                    sprintf(": %s",$responseJson["message"]): "" ;
            }
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
        $newSeries->setFrom($from);
        $newSeries->setUntil($until);
        $newSeries->setStep($step);
        $newSeries->setValues($values);
        return $newSeries;
    }

    /**
     * Influx service main entry point.
     *
     * @param string $query
     * @param string $db_name
     * @return TimeSeries
     * @throws BackendException
     */
    public function queryInflux(string $query, string $db_name): TimeSeries
    {
        // send query and process response
        $res = $this->sendQuery($query, $db_name);
        return $this->processResponseJson($res);
    }
}
