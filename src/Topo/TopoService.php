<?php

namespace App\Topo;


class TopoService
{
    const DB_PATH_PFX = '/var/topojson';

    const DATABASES = [
        'natural-earth' => [
            'ne_10m_admin_0.continents.v3.1.0',
            'ne_10m_admin_0.countries.v3.1.0',
            'ne_10m_admin_1.regions.v3.0.0',
        ],
        'gadm' => [
            'gadm.counties.v2.0',
        ],
    ];

    /**
     * Get a list of supported databases.
     *
     * @return string[]
     */
    public function getDatabases(): array
    {
        return array_keys(TopoService::DATABASES);
    }

    /**
     * Get a list of tables for the given database.
     *
     * @var string $db
     * @return string[]
     */
    public function getTables(string $db): array
    {
        if (!array_key_exists($db, TopoService::DATABASES)) {
            throw new \InvalidArgumentException("Invalid database '$db'");
        }
        return TopoService::DATABASES[$db];
    }

    public function getTopoJson(string $db, string $table): array
    {
        if (!array_key_exists($db, TopoService::DATABASES)) {
            throw new \InvalidArgumentException("Invalid database '$db'");
        }
        if (!in_array($table, TopoService::DATABASES[$db])) {
            throw new \InvalidArgumentException("Invalid table '$table'");
        }
        // build the file path
        // TODO move this data to swift?
        $file = implode('/', [TopoService::DB_PATH_PFX, $db, $table]) . '.processed.topo.json';
        if (!file_exists($file)) {
            throw new \InvalidArgumentException("Could not load TopoJson for $db/$table ($file)");
        }
        // TODO: if this is too slow, remove envelope and return file contents
        // TODO: directly to save the json decode/encode step
        return json_decode(file_get_contents($file), true);
    }
}
