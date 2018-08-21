<?php

namespace App\TimeSeries;


use App\Expression\AbstractExpression;
use Symfony\Component\Serializer\Annotation\Groups;

class TimeSeriesSet
{

    /**
     * @var TimeSeries[] $series
     * @Groups("public")
     */
    protected $seriesSet;

    public function __construct()
    {
        $this->seriesSet = [];
    }

    /**
     * @return TimeSeries[]
     */
    public function getSeriesSet(): array
    {
        return $this->seriesSet;
    }

    /**
     * @param TimeSeries[] $seriesSet
     */
    public function setSeriesSet(array $seriesSet): void
    {
        $this->seriesSet = $seriesSet;
    }

    /**
     * @param TimeSeries $series
     */
    public function addSeries(TimeSeries $series): void
    {
        $this->seriesSet[$series->getExpression()->getCanonicalStr()] = $series;
    }

    /**
     * Gets a series based on its expression
     *
     * @param AbstractExpression $expression
     * @return TimeSeries
     */
    public
    function getSeries(AbstractExpression $expression): TimeSeries
    {
        return $this->seriesSet[$expression->getCanonicalStr()];
    }

    /**
     * Down-samples each series in the set, attempting to stay within the
     * specified number of points
     *
     * @param int $maxPoints
     */
    public function downSample(int $maxPoints): void
    {
        if (!$this->seriesSet || !count($this->seriesSet)) {
            return;
        }
        // first we need to know the total number of points that we have
        $numPoints = 0;
        foreach ($this->seriesSet as $series) {
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
        foreach ($this->seriesSet as $series) {
            $series->downSample($reductionRatio);
        }
    }
}
