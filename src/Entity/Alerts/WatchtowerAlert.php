<?php

namespace App\Entity\Alerts;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

// TODO: Groups public?
// TODO: GeneratedValue()?
// TODO: ORM\Table?
/**
 * @ORM\Entity(repositoryClass="App\Repository\AlertRepository")
 * @ORM\Table(indexes={@ORM\Index(name="long_url_idx", columns={"long_url"})})
 */
class WatchtowerAlert
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
     * @return WatchtowerAlert
     */
    public function setId(int $id): WatchtowerAlert
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
     * @return WatchtowerAlert
     */
    public function setFqid(string $fqid): WatchtowerAlert
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
     * @return WatchtowerAlert
     */
    public function setName(string $name): WatchtowerAlert
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
     * @return WatchtowerAlert
     */
    public function setQueryTime(int $queryTime): WatchtowerAlert
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
     * @return WatchtowerAlert
     */
    public function setTime(int $time): WatchtowerAlert
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
     * @return WatchtowerAlert
     */
    public function setLevel(string $level): WatchtowerAlert
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
     * @return WatchtowerAlert
     */
    public function setMethod(string $method): WatchtowerAlert
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
     * @return WatchtowerAlert
     */
    public function setExpression(string $expression): WatchtowerAlert
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
     * @return WatchtowerAlert
     */
    public function setQueryExpression(string $queryExpression): WatchtowerAlert
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
     * @return WatchtowerAlert
     */
    public function setHistoryQueryExpression(string $historyQueryExpression): WatchtowerAlert
    {
        $this->historyQueryExpression = $historyQueryExpression;
        return $this;
    }

    /**
     * @return string
     */
    public function getCondition(): string
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     * @return WatchtowerAlert
     */
    public function setCondition(string $condition): WatchtowerAlert
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
     * @return WatchtowerAlert
     */
    public function setValue(float $value): WatchtowerAlert
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
     * @return WatchtowerAlert
     */
    public function setHistoryValue(float $historyValue): WatchtowerAlert
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
     * @return WatchtowerAlert
     */
    public function setMetaType(string $metaType): WatchtowerAlert
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
     * @return WatchtowerAlert
     */
    public function setMetaCode(string $metaCode): WatchtowerAlert
    {
        $this->metaCode = $metaCode;
        return $this;
    }

    //////////////////////////
    //////////////////////////
    // VARIABLE DEFINITIONS //
    //////////////////////////
    //////////////////////////

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
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


}
