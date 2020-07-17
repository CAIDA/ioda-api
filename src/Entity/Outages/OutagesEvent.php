<?php

namespace App\Entity\Outages;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\Ioda\MetadataEntity;

class OutagesEvent
{
    /**
     * Constructor
     */
    public function __construct($from, $until, $alerts, $score, $format, $includeAlerts, $overlap)
    {
        $this->from = $from;
        $this->until = $until;
        $this->alerts = $alerts;
        $this->score = $score;
        $this->format = $format;
        $this->includeAlerts = $includeAlerts;
        $this->overlap = $overlap;
    }

    /////////////////////
    /////////////////////
    // GETTERS SETTERS //
    /////////////////////
    /////////////////////

    /**
     * @return bool
     */
    public function isIncludeAlerts(): bool
    {
        return $this->includeAlerts;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param string $format
     * @return OutagesEvent
     */
    public function setFormat(string $format): OutagesEvent
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @return MetadataEntity
     */
    public function getEntity(): ?MetadataEntity
    {
        if(!$this->alerts){
            return null;
        }
        return $this->alerts[0]->getEntity();
    }

    /**
     * @return int
     */
    public function getFrom(): int
    {
        return $this->from;
    }

    /**
     * @param int $from
     * @return OutagesEvent
     */
    public function setFrom(int $from): OutagesEvent
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @return int
     */
    public function getUntil(): int
    {
        return $this->until;
    }

    /**
     * @param int $until
     * @return OutagesEvent
     */
    public function setUntil(int $until): OutagesEvent
    {
        $this->until = $until;
        return $this;
    }

    /**
     * @return float
     */
    public function getScore(): float
    {
        return $this->score;
    }

    /**
     * @param float $score
     * @return OutagesEvent
     */
    public function setScore(float $score): OutagesEvent
    {
        $this->score = $score;
        return $this;
    }

    /**
     * @return OutagesAlert[]
     */
    public function getAlerts(): ?array
    {
        return $this->alerts;
    }

    /**
     * @param OutagesAlert[] $alerts
     * @return OutagesEvent
     */
    public function setAlerts(array $alerts): OutagesEvent
    {
        $this->alerts = $alerts;
        return $this;
    }

    /**
     * @return string
     */
    public function getDatasource(): string
    {
        return $this->alerts[0]->getDatasource();
    }

    /**
     * @return bool
     */
    public function isOverlap(): bool
    {
        return $this->overlap;
    }

    /**
     * @param bool $overlap
     * @return OutagesEvent
     */
    public function setOverlap(bool $overlap): OutagesEvent
    {
        $this->overlap = $overlap;
        return $this;
    }

    //////////////////////////
    //////////////////////////
    // VARIABLE DEFINITIONS //
    //////////////////////////
    //////////////////////////

    /**
     * @Groups({"public"})
     * @var MetadataEntity
     */
    private $entity;

    /**
     * @Groups({"public"})
     * @var integer
     */
    private $from;

    /**
     * @Groups({"public"})
     * @var integer
     */
    private $until;

    /**
     * @Groups({"public"})
     * @var float
     */
    private $score;

    /**
     * @Groups({"public"})
     * @var OutagesAlert[]
     */
    private $alerts;

    /**
     * @Groups({"public"})
     * @var string
     */
    private $datasource;

    /**
     * @var string
     */
    private $format;

    /**
     * @var bool
     */
    private $includeAlerts;

    /**
     * @var bool
     */
    private $overlap;

}
