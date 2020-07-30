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

    /**
     * @var DatasourceEntity[]
     */
    private $DATASOURCES_ENTITIES;

    public function __construct()
    {
        $this->DATASOURCES_ENTITIES = [
            "ucsd-nt" => new DatasourceEntity(
                "ucsd-nt",
                "UCSD Network Telescope",
                "Unique Source IPs",
                60,
                "influx"
            ),
            "bgp" => new DatasourceEntity(
                "bgp",
                "BGP",
                "Visible /24s",
                300,
                "graphite"
            ),
            "ping-slash24" => new DatasourceEntity(
                "ping-slash24",
                "Active Probing",
                "Up /24s",
                600,
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
