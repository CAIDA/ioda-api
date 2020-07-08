<?php

namespace App\Entity\Outages;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

// TODO: Groups public?
// TODO: GeneratedValue()?
// TODO: ORM\Table?
/**
 * @ORM\Entity(repositoryClass="App\Repository\OutagersAlertsRepository")
 * @ORM\Table(name="watchtower_alert")
 */
class OutagesAlert
{

    /////////////////////
    /////////////////////
    // GETTERS SETTERS //
    /////////////////////
    /////////////////////

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return OutagesAlert
     */
    public function setId(int $id): OutagesAlert
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getFqid(): string
    {
        return $this->fqid;
    }

    /**
     * @param string $fqid
     * @return OutagesAlert
     */
    public function setFqid(string $fqid): OutagesAlert
    {
        $this->fqid = $fqid;
        return $this;
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
     * @return OutagesAlert
     */
    public function setName(string $name): OutagesAlert
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getQueryTime(): int
    {
        return $this->queryTime;
    }

    /**
     * @param int $queryTime
     * @return OutagesAlert
     */
    public function setQueryTime(int $queryTime): OutagesAlert
    {
        $this->queryTime = $queryTime;
        return $this;
    }

    /**
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * @param int $time
     * @return OutagesAlert
     */
    public function setTime(int $time): OutagesAlert
    {
        $this->time = $time;
        return $this;
    }

    /**
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * @param string $level
     * @return OutagesAlert
     */
    public function setLevel(string $level): OutagesAlert
    {
        $this->level = $level;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return OutagesAlert
     */
    public function setMethod(string $method): OutagesAlert
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * @param string $expression
     * @return OutagesAlert
     */
    public function setExpression(string $expression): OutagesAlert
    {
        $this->expression = $expression;
        return $this;
    }

    /**
     * @return string
     */
    public function getQueryExpression(): string
    {
        return $this->queryExpression;
    }

    /**
     * @param string $queryExpression
     * @return OutagesAlert
     */
    public function setQueryExpression(string $queryExpression): OutagesAlert
    {
        $this->queryExpression = $queryExpression;
        return $this;
    }

    /**
     * @return string
     */
    public function getHistoryQueryExpression(): string
    {
        return $this->historyQueryExpression;
    }

    /**
     * @param string $historyQueryExpression
     * @return OutagesAlert
     */
    public function setHistoryQueryExpression(string $historyQueryExpression): OutagesAlert
    {
        $this->historyQueryExpression = $historyQueryExpression;
        return $this;
    }

    /**
     * @return string
     */
    public function getCondition(): ?string
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     * @return OutagesAlert
     */
    public function setCondition(string $condition): OutagesAlert
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * @param float $value
     * @return OutagesAlert
     */
    public function setValue(float $value): OutagesAlert
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return float
     */
    public function getHistoryValue(): float
    {
        return $this->historyValue;
    }

    /**
     * @param float $historyValue
     * @return OutagesAlert
     */
    public function setHistoryValue(float $historyValue): OutagesAlert
    {
        $this->historyValue = $historyValue;
        return $this;
    }

    /**
     * @return string
     */
    public function getMetaType(): string
    {
        return $this->metaType;
    }

    /**
     * @param string $metaType
     * @return OutagesAlert
     */
    public function setMetaType(string $metaType): OutagesAlert
    {
        $this->metaType = $metaType;
        return $this;
    }

    /**
     * @return string
     */
    public function getMetaCode(): string
    {
        return $this->metaCode;
    }

    /**
     * @param string $metaCode
     * @return OutagesAlert
     */
    public function setMetaCode(string $metaCode): OutagesAlert
    {
        $this->metaCode = $metaCode;
        return $this;
    }

    /**
     */
    public function getEntity(): MetadataEntity
    {
        return $this->entity;
    }

    /**
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     */
    public function getDatasource()
    {
        return $this->datasource;
    }

    /**
     */
    public function setDatasource($datasource)
    {
        $this->datasource = $datasource;
        return $this;
    }

    //////////////////////////
    //////////////////////////
    // VARIABLE DEFINITIONS //
    //////////////////////////
    //////////////////////////

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Groups({"public"})
     * @var string
     */
    private $fqid;

    /**
     * @ORM\Column(type="string")
     * @Groups({"public"})
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"public"})
     * @var integer
     */
    private $queryTime;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"public"})
     * @var integer
     */
    private $time;

    /**
     * @ORM\Column(type="string")
     * @Groups({"public"})
     * @var string
     */
    private $level;

    /**
     * @ORM\Column(type="string")
     * @Groups({"public"})
     * @var string
     */
    private $method;

    /**
     * @ORM\Column(type="string")
     * @Groups({"public"})
     * @var string
     */
    private $expression;

    /**
     * @ORM\Column(type="string")
     * @Groups({"public"})
     * @var string
     */
    private $queryExpression;

    /**
     * @ORM\Column(type="string")
     * @Groups({"public"})
     * @var string
     */
    private $historyQueryExpression;

    /**
     * @ORM\Column(type="string")
     * @Groups({"public"})
     * @var string
     */
    private $condition;

    /**
     * @ORM\Column(type="float")
     * @Groups({"public"})
     * @var float
     */
    private $value;

    /**
     * @ORM\Column(type="float")
     * @Groups({"public"})
     * @var float
     */
    private $historyValue;

    /**
     * @ORM\Column(type="string")
     * @Groups({"public"})
     * @var string
     */
    private $metaType;

    /**
     * @ORM\Column(type="string")
     * @Groups({"public"})
     * @var string
     */
    private $metaCode;

    /**
     * @Groups({"public"})
     * @var MetadataEntity
     */
    private $entity;

    /**
     * @Groups({"public"})
     * @var string
     */
    private $datasource;

}
