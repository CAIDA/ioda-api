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
use App\Entity\Ioda\DatasourceEntity;


class DatasourceService
{

    const steps_1_minute = [
                    60, 120, 300, 900, 1800, // minute-level [1, 2, 5, 15, 30]
                    3600, 7200, 21600, 43200,  //hour-level [1, 2, 6, 12]
                    86400, 172800, //day-level [1, 2]
                    604800, 1209600, 2419200, //week-level [1, 2, 4]
                    31536000, 63072000, 315360000, //year-level [1, 2, 10]
                ];

    const steps_5_minute = [
        300, 900, 1800, // minute-level [1, 2, 5, 15, 30]
        3600, 7200, 21600, 43200,  //hour-level [1, 2, 6, 12]
        86400, 172800, //day-level [1, 2]
        604800, 1209600, 2419200, //week-level [1, 2, 4]
        31536000, 63072000, 315360000, //year-level [1, 2, 10]
    ];

    public function __construct()
    {
        $this->DATASOURCES_ENTITIES = [
            "ucsd-nt" => new DatasourceEntity(
                "ucsd-nt",
                "UCSD Network Telescope",
                "Unique Source IPs",
                self::steps_1_minute,
                "influx"
            ),
            "bgp" => new DatasourceEntity(
                "bgp",
                "BGP",
                "Visible /24s",
                self::steps_1_minute,
                "graphite"
            ),
            "ping-slash24" => new DatasourceEntity(
                "ping-slash24",
                "Active Probing",
                "Up /24s",
                self::steps_1_minute,
                "graphite"
            ),
        ];
    }

    public function getAllDatasources(){
        return array_values($this->DATASOURCES_ENTITIES);
    }

    public function getDatasource(String $name){
        if (!array_key_exists($name, $this->DATASOURCES_ENTITIES)) {
            throw new \InvalidArgumentException("Unknown datasource '$name'");
        }
        return $this->DATASOURCES_ENTITIES[$name];
    }

    public function getDatasourceNames(){
        return array_keys($this->DATASOURCES_ENTITIES);
    }

    public function isValidDatasource(string $ds_name): bool {
        return array_key_exists($ds_name,$this->DATASOURCES_ENTITIES);
    }

    public function fqidToDatasourceName($fqid){
        $ds = null;
        if(strpos($fqid,"bgp")!==false){
            $ds = "bgp";
        } elseif (strpos($fqid,"ucsd-nt")!==false){
            $ds = "ucsd-nt";
        } elseif (strpos($fqid,"ping-slash24")!==false){
            $ds = "ping-slash24";
        }

        return $ds;
    }
}
