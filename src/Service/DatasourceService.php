<?php


namespace App\Service;


class DatasourceService
{
    const DATASOURCES = [
        "ucsd-nt" => [
            "datasource" => "ucsd-nt",
            "name" => "UCSD Network Telescope",
            "units" => "Unique Source IPs"
        ],
        "bgp" => [
            "datasource" => "bgp",
            "name" => "BGP",
            "units" => "Visible /24s"
        ],
        "ping-slash24" => [
            "datasource" => "ping-slash24",
            "name" => "Active Probing",
            "units" => "Up /24s"
        ],
    ];

    public function getDatasources(){
        return self::DATASOURCES;
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
