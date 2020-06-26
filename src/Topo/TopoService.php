<?php

namespace App\Topo;


class TopoService
{
    const DB_PATH_PFX = '/var/topojson';

    const ENTITY_TYPE_TO_DB = [
        "continent" => ["natural-earth", "ne_10m_admin_0.continents.v3.1.0"],
        "country" => ["natural-earth", "ne_10m_admin_0.countries.v3.1.0"],
        "region" => ["natural-earth", "ne_10m_admin_1.regions.v3.0.0"],
        "county" => ["gadm", "gadm.counties.v2.0"],
    ];

    const ENTITY_TYPE_TO_ID_FIELD = [
        "continent" => "usercode",
        "country" => "usercode",
        "region" => "id",
        "county" => "id",
    ];

    public function getTopoJson(string $entityType): array
    {
        if (!array_key_exists($entityType, self::ENTITY_TYPE_TO_DB)) {
            throw new \InvalidArgumentException("Invalid entity type '$entityType'");
        }
        // build the file path
        // TODO move this data to swift?
        $db = self::ENTITY_TYPE_TO_DB[$entityType][0];
        $table = self::ENTITY_TYPE_TO_DB[$entityType][1];
        $file = implode('/', [TopoService::DB_PATH_PFX, $db, $table]) . '.processed.topo.json';
        if (!file_exists($file)) {
            throw new \InvalidArgumentException("Could not load TopoJson for $db/$table ($file)");
        }
        // TODO: if this is too slow, remove envelope and return file contents
        // TODO: directly to save the json decode/encode step
        return json_decode(file_get_contents($file), true);
    }
    public function getIdField(string $entityType): string
    {
        if (!array_key_exists($entityType, self::ENTITY_TYPE_TO_ID_FIELD)) {
            throw new \InvalidArgumentException("Invalid entity type '$entityType'");
        }
        return self::ENTITY_TYPE_TO_ID_FIELD[$entityType];
    }
}
