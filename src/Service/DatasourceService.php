<?php


namespace App\Service;
use App\Entity\Ioda\DatasourceEntity;


class DatasourceService
{

    public function __construct()
    {
        $this->DATASOURCES_ENTITIES = [
            "ucsd-nt" => new DatasourceEntity("ucsd-nt", "UCSD Network Telescope", "Unique Source IPs"),
            "bgp" => new DatasourceEntity("bgp", "BGP", "Visible /24s"),
            "ping-slash24" => new DatasourceEntity("ping-slash24", "Active Probing", "Up /24s"),
        ];
    }



    public function getDatasources(){
        return $this->DATASOURCES_ENTITIES;
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
