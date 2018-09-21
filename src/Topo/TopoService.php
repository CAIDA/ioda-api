<?php

namespace App\Topo;


class TopoService
{
    const DATABASES = [
        'natural-earth',
        'gadm',
    ];

    public function getDatabases(): array
    {
        return TopoService::DATABASES;
    }
}