<?php

namespace App\TimeSeries;


use App\Expression\AbstractExpression;

class TimeSeriesSummary
{
    /**
     * @var \DateTime
     */
    protected $earliestFrom;

    /**
     * @var \DateTime
     */
    protected $lastUntil;

    /**
     * @var int[]
     */
    protected $steps;

    /**
     * @var int[]
     */
    protected $nativeSteps;

    /**
     * @var AbstractExpression
     * TODO: consider a dedicated ExpressionPrefix/Suffix class?
     */
    protected $commonPrefix;

    /**
     * @var AbstractExpression
     */
    protected $commonSuffix;

    public function __construct()
    {
        $this->steps = [];
        $this->nativeSteps = [];
    }

    /**
     * @return \DateTime
     */
    public function getEarliestFrom(): \DateTime
    {
        return $this->earliestFrom;
    }

    /**
     * @param \DateTime $earliestFrom
     */
    public function setEarliestFrom(\DateTime $earliestFrom): void
    {
        $this->earliestFrom = $earliestFrom;
    }

    /**
     * @param \DateTime $from
     */
    public function addFrom(\DateTime $from): void
    {
        if (!isset($this->earliestFrom) || $from < $this->earliestFrom) {
            $this->earliestFrom = $from;
        }
    }

    /**
     * @return \DateTime
     */
    public function getLastUntil(): \DateTime
    {
        return $this->lastUntil;
    }

    /**
     * @param \DateTime $lastUntil
     */
    public function setLastUntil(\DateTime $lastUntil): void
    {
        $this->lastUntil = $lastUntil;
    }

    /**
     * @param \DateTime $until
     */
    public function addUntil(\DateTime $until): void
    {
        if (!isset($this->earliestUntil) || $until > $this->earliestUntil) {
            $this->earliestUntil = $until;
        }
    }

    /**
     * @return int[]
     */
    public function getSteps(): array
    {
        return array_keys($this->steps);
    }

    public function addStep(int $step): void
    {
        $this->steps[$step] = 1;
    }

    /**
     * @return int[]
     */
    public function getNativeSteps(): array
    {
        return $this->nativeSteps;
    }

    /**
     * @return AbstractExpression
     */
    public function getCommonPrefix(): AbstractExpression
    {
        return $this->commonPrefix;
    }

    /**
     * @param AbstractExpression $commonPrefix
     */
    public function setCommonPrefix(AbstractExpression $commonPrefix): void
    {
        $this->commonPrefix = $commonPrefix;
    }

    /**
     * @return AbstractExpression
     */
    public function getCommonSuffix(): AbstractExpression
    {
        return $this->commonSuffix;
    }

    /**
     * @param AbstractExpression $commonSuffix
     */
    public function setCommonSuffix(AbstractExpression $commonSuffix): void
    {
        $this->commonSuffix = $commonSuffix;
    }


}