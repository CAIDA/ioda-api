<?php

namespace App\TimeSeries;


use App\Expression\AbstractExpression;
use Symfony\Component\Serializer\Annotation\Groups;

class TimeSeriesSummary
{
    /**
     * @var \DateTime
     * @Groups("public")
     */
    protected $earliestFrom;

    /**
     * @var \DateTime
     * @Groups("public")
     */
    protected $lastUntil;

    /**
     * @var int[]
     * @Groups("public")
     */
    protected $steps;

    /**
     * @var int[]
     * @Groups("public")
     */
    protected $nativeSteps;

    /**
     * @var AbstractExpression
     * TODO: consider a dedicated ExpressionPrefix/Suffix class?
     * @Groups("public")
     */
    protected $commonPrefix;

    /**
     * @var AbstractExpression
     * @Groups("public")
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
    public function getEarliestFrom(): ?\DateTime
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
    public function getLastUntil(): ?\DateTime
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
        if (!isset($this->lastUntil) || $until > $this->lastUntil) {
            $this->lastUntil = $until;
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
    public function getNativeSteps(): ?array
    {
        if (count($this->nativeSteps) == 0) {
            return null;
        }
        $result = [];
        $nativeSteps = array_keys($this->nativeSteps);
        foreach ($nativeSteps as $ns) {
            $result[$ns] = array_keys($this->nativeSteps[$ns]);
        }
        return $result;
    }

    public function addNativeStep(int $nativeStep, int $step): void
    {
        if (!array_key_exists((int)$nativeStep, $this->nativeSteps)) {
            $this->nativeSteps[(int)$nativeStep] = [];
        }
        $this->nativeSteps[(int)$nativeStep][(int)$step] = 1;
    }

    /**
     * @return AbstractExpression
     */
    public function getCommonPrefix(): ?AbstractExpression
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
    public function getCommonSuffix(): ?AbstractExpression
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
