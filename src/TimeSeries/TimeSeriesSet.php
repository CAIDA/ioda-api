<?php

namespace App\TimeSeries;


use App\Expression\AbstractExpression;
use Symfony\Component\Serializer\Annotation\Groups;

class TimeSeriesSet
{

    /**
     * @var TimeSeries[]
     * @Groups("public")
     */
    protected $series;

    /**
     * @var TimeSeriesSummary
     * @Groups("public")
     */
    protected $summary;

    public function __construct()
    {
        $this->series = [];
        $this->summary = new TimeSeriesSummary();
    }

    /**
     * @return TimeSeries[]
     */
    public function getSeries(): array
    {
        return $this->series;
    }

    /**
     * @param TimeSeries[] $series
     */
    public function setSeries(array $series): void
    {
        $this->series = $series;
    }

    /**
     * @return TimeSeriesSummary
     */
    public function getSummary(): TimeSeriesSummary
    {
        return $this->summary;
    }

    /**
     * @param TimeSeriesSummary $summary
     */
    public function setSummary(TimeSeriesSummary $summary): void
    {
        $this->summary = $summary;
    }

    /**
     * @param TimeSeries $series
     */
    public function addOneSeries(TimeSeries $series): void
    {
        $this->series[$series->getExpression()->getCanonicalStr()] = $series;
    }

    /**
     * Gets a series based on its expression
     *
     * @param AbstractExpression $expression
     * @return TimeSeries
     */
    public function getSeriesByExpression(AbstractExpression $expression): TimeSeries
    {
        return $this->series[$expression->getCanonicalStr()];
    }

    /**
     * Down-samples each series in the set, attempting to stay within the
     * specified number of points
     *
     * @param int $maxPoints
     * @param string $aggrFunc
     */
    public function downSample(int $maxPoints, string $aggrFunc): void
    {
        if (!$this->series || !count($this->series)) {
            return;
        }
        // first we need to know the total number of points that we have
        $numPoints = 0;
        foreach ($this->series as $series) {
            $numPoints += $series->getNumPoints();
        }
        // if we have 0 points, just give up
        if (!$numPoints) {
            return;
        }
        // now we need to know how much to reduce each series by
        $reductionRatio = $maxPoints / $numPoints;
        if ($reductionRatio > 1) { // don't need to reduce
            return;
        }
        foreach ($this->series as $series) {
            $series->downSample($reductionRatio, $aggrFunc);
        }
    }
}
