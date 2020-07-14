<?php

namespace App\Entity\Ioda;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

class DatasourceEntity
{

    /**
     * Constructor
     */
    public function __construct(string $datasource, string $name, string $units)
    {
        $this->datasource = $datasource;
        $this->name = $name;
        $this->units = $units;
    }


    //////////////////////////
    //////////////////////////
    // VARIABLE DEFINITIONS //
    //////////////////////////
    //////////////////////////

    /**
     * @Groups({"public"})
     * @var string
     */
    private $datasource;

    /**
     * @Groups({"public"})
     * @var string
     */
    private $name;

    /**
     * @Groups({"public"})
     * @var string
     */
    private $units;


    /////////////////////
    /////////////////////
    // GETTERS SETTERS //
    /////////////////////
    /////////////////////


    /**
     * @param string $datasource
     * @return DatasourceEntity
     */
    public function setDatasource(string $datasource): DatasourceEntity
    {
        $this->datasource = $datasource;
        return $this;
    }

    /**
     * @return string
     */
    public function getDatasource(): string
    {
        return $this->datasource;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return DatasourceEntity
     */
    public function setName(string $name): DatasourceEntity
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getUnits(): string
    {
        return $this->units;
    }

    /**
     * @param string $units
     * @return DatasourceEntity
     */
    public function setUnits(string $units): DatasourceEntity
    {
        $this->units = $units;
        return $this;
    }

}
