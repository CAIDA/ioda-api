<?php

namespace App\Entity\Outages;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\Ioda\MetadataEntity;

class OutagesSummary
{
    /**
     * Constructor
     */
    public function __construct($scores, $entity)
    {
        $this->scores = $scores;
        $this->entity = $entity;
    }

    /////////////////////
    /////////////////////
    // GETTERS SETTERS //
    /////////////////////
    /////////////////////

    /**
     * @return array
     */
    public function getScores(): array
    {
        return $this->scores;
    }

    /**
     * @param array $scores
     * @return OutagesSummary
     */
    public function setScores(array $scores): OutagesSummary
    {
        $this->scores = $scores;
        return $this;
    }

    /**
     * @return MetadataEntity
     */
    public function getEntity(): MetadataEntity
    {
        return $this->entity;
    }

    /**
     * @param MetadataEntity $entity
     * @return OutagesSummary
     */
    public function setEntity(MetadataEntity $entity): OutagesSummary
    {
        $this->entity = $entity;
        return $this;
    }



    //////////////////////////
    //////////////////////////
    // VARIABLE DEFINITIONS //
    //////////////////////////
    //////////////////////////

    /**
     * @Groups({"public"})
     * @var array
     */
    private $scores;

    /**
     * @Groups({"public"})
     * @var MetadataEntity
     */
    private $entity;
}
