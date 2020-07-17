<?php
/**
 * This software is Copyright (c) 2013 The Regents of the University of
 * California. All Rights Reserved. Permission to copy, modify, and distribute this
 * software and its documentation for academic research and education purposes,
 * without fee, and without a written agreement is hereby granted, provided that
 * the above copyright notice, this paragraph and the following three paragraphs
 * appear in all copies. Permission to make use of this software for other than
 * academic research and education purposes may be obtained by contacting:
 *
 * Office of Innovation and Commercialization
 * 9500 Gilman Drive, Mail Code 0910
 * University of California
 * La Jolla, CA 92093-0910
 * (858) 534-5815
 * invent@ucsd.edu
 *
 * This software program and documentation are copyrighted by The Regents of the
 * University of California. The software program and documentation are supplied
 * "as is", without any accompanying services from The Regents. The Regents does
 * not warrant that the operation of the program will be uninterrupted or
 * error-free. The end-user understands that the program was developed for research
 * purposes and is advised not to rely exclusively on the program for any reason.
 *
 * IN NO EVENT SHALL THE UNIVERSITY OF CALIFORNIA BE LIABLE TO ANY PARTY FOR
 * DIRECT, INDIRECT, SPECIAL, INCIDENTAL, OR CONSEQUENTIAL DAMAGES, INCLUDING LOST
 * PROFITS, ARISING OUT OF THE USE OF THIS SOFTWARE AND ITS DOCUMENTATION, EVEN IF
 * THE UNIVERSITY OF CALIFORNIA HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE. THE UNIVERSITY OF CALIFORNIA SPECIFICALLY DISCLAIMS ANY WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE. THE SOFTWARE PROVIDED HEREUNDER IS ON AN "AS
 * IS" BASIS, AND THE UNIVERSITY OF CALIFORNIA HAS NO OBLIGATIONS TO PROVIDE
 * MAINTENANCE, SUPPORT, UPDATES, ENHANCEMENTS, OR MODIFICATIONS.
 */

namespace App\TimeSeries;


use App\Entity\Ioda\MetadataEntity;
use App\Expression\AbstractExpression;
use App\TimeSeries\Annotation\AbstractAnnotation;
use Symfony\Component\Serializer\Annotation\Groups;

class TimeSeries
{
    /**
     * These are the "human-usable" steps that we will downsample to
     */
    const ALLOWED_STEPS = [
        60, 120, 300, 900, 1800, // minute-level [1, 2, 5, 15, 30]
        3600, 7200, 21600, 43200,  //hour-level [1, 2, 6, 12]
        86400, 172800, //day-level [1, 2]
        604800, 1209600, 2419200, //week-level [1, 2, 4]
        31536000, 63072000, 315360000, //year-level [1, 2, 10]
    ];

    /**
     * Expression that resulted in this time series.
     *
     * @var AbstractExpression
     * @Groups("public")
     */
    protected $expression;

    /**
     * Contextual name of this time series.
     *
     * Generated based on the expressions of other time series in a given
     * TimeSeriesSet.
     *
     * @var string
     * @Groups({"public"})
     */
    protected $contextualName;

    /**
     * Time of the first data point in this time series.
     *
     * @var \DateTime
     * @Groups("public")
     */
    protected $from;

    /**
     * Time after the last data point in this time series.
     * I.e., last_data_point + step.
     *
     * @var \DateTime
     * @Groups("public")
     */
    protected $until;

    /**
     * Time (in seconds) between data points in this time series (after any
     * down-sampling).
     *
     * @var int
     * @Groups("public")
     */
    protected $step;

    /**
     * Time (in seconds) between data points in the original time series (before
     * any down-sampling).
     *
     * @var int
     * @Groups("public")
     */
    protected $nativeStep;

    /**
     * List of annotations for this time series
     * @var AbstractAnnotation[]
     * @Groups("public")
     */
    protected $annotations;

    /**
     * Array of time series data points. The first point is at $from, each point
     * is $step seconds apart, and the last point is at ($until-$from).
     *
     * TODO: consider using a more efficient array implementation
     * e.g., https://github.com/php-ds/extension
     *
     * @var int[]
     * @Groups("public")
     */
    protected $values;

    /**
     * @var string
     *  @Groups("public")
     */
    protected $datasource;

    /**
     * @var MetadataEntity
     * @Groups("public")
     */
    protected $metadataEntity;

    public function setDatasource(string $datasource)
    {
        $this->datasource = $datasource;
    }

    public function getDatasource(): string
    {
        return $this->datasource;
    }

    public function setMetadataEntity(MetadataEntity $metadataEntity)
    {
        $this->metadataEntity = $metadataEntity;
    }

    public function getMetadataEntity(): MetadataEntity
    {
        return $this->metadataEntity;
    }

    /**
     * TimeSeries constructor.
     *
     * TODO: Summary
     *
     * @param AbstractExpression $expression
     */
    public function __construct(AbstractExpression $expression)
    {
        $this->expression = $expression;
    }

    public function getExpression(): AbstractExpression
    {
        return $this->expression;
    }

    public function getContextualName(): string
    {
        return $this->contextualName;
    }

    /**
     * Update the contextual name of this series based on a completed series
     * summary object.
     *
     * @param TimeSeriesSummary $seriesSummary
     */
    public function updateContextualName(TimeSeriesSummary $seriesSummary): void
    {
        $this->contextualName =
            $this->getExpression()->getCanonicalHumanized(
                $seriesSummary->getCommonPrefix(),
                $seriesSummary->getCommonSuffix()
            );
    }

    /**
     * @return \DateTime
     */
    public function getFrom(): \DateTime
    {
        return $this->from;
    }

    /**
     * @return int
     */
    public function getFromEpoch(): int
    {
        return $this->from->getTimestamp();
    }

    /**
     * @param \DateTime $from
     */
    public function setFrom(\DateTime $from): void
    {
        $this->from = $from;
    }

    /**
     * @param int $epoch
     */
    public function setFromEpoch(int $epoch): void
    {
        if (!$this->from) {
            $this->from = new \DateTime();
        }
        $this->from->setTimestamp($epoch);
    }

    /**
     * @return \DateTime
     */
    public function getUntil(): \DateTime
    {
        return $this->until;
    }

    /**
     * @return int
     */
    public function getUntilEpoch(): int
    {
        return $this->until->getTimestamp();
    }

    /**
     * @param \DateTime $until
     */
    public function setUntil(\DateTime $until): void
    {
        $this->until = $until;
    }

    /**
     * @param int $epoch
     */
    public function setUntilEpoch(int $epoch): void
    {
        if (!$this->until) {
            $this->until = new \DateTime();
        }
        $this->until->setTimestamp($epoch);
    }

    /**
     * @return int
     */
    public function getStep(): int
    {
        return $this->step;
    }

    /**
     * @param int $step
     */
    public function setStep(int $step): void
    {
        $this->step = $step;

        /* if the native step has not been set, use this as the default */
        if (!$this->nativeStep) {
            $this->setNativeStep($step);
        }
    }

    /**
     * @return int
     */
    public function getNativeStep(): int
    {
        return $this->nativeStep;
    }

    /**
     * @param int $nativeStep
     */
    public function setNativeStep(int $nativeStep): void
    {
        $this->nativeStep = $nativeStep;
    }

    /**
     * @return int[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param int[] $values
     */
    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    /**
     * Add a time series data point
     *
     * @param int $value
     */
    public function addValue($value): void
    {
        if (!$this->values) {
            $this->values = array();
        }
        $this->values[] = $value;
    }

    public function getNumPoints(): int
    {
        return count($this->values);
    }

    /**
     * @return AbstractAnnotation[]
     */
    public function getAnnotations(): ?array
    {
        return $this->annotations;
    }

    /**
     * @param AbstractAnnotation[] $annotations
     */
    public function setAnnotations(?array $annotations): void
    {
        $this->annotations = $annotations;
    }

    /* ======= DOWN-SAMPLING ======== */

    /**
     * Given a reduction ratio and step, find the best "allowed" down-sampled
     * step
     *
     * @param float $ratio
     * @param int $oldStep
     * @return int
     */
    private function findDownSampleStep(float $ratio, int $oldStep): int
    {
        $interval = $this->getUntilEpoch() - $this->getFromEpoch();
        $idealStep = $oldStep / $ratio;
        // we know our ideal step, but find the largest allowed step that is
        // smaller than the ideal step
        $newStep = TimeSeries::ALLOWED_STEPS[0]; // 1 min is the lowest
        $hit = false;
        foreach (TimeSeries::ALLOWED_STEPS as $allowedStep) {
            if ($hit == true) {
                break;
            }
            if ($allowedStep < $interval) {
                $newStep = $allowedStep;
            }
            if ($newStep >= $oldStep && $allowedStep >= $idealStep) {
                $hit = true;
            }
        }
        return $newStep;
    }

    /**
     * Down-sample the current data points to the given step, using the given
     * aggregation function
     *
     * @param int $newStep
     * @param int $oldStep
     * @param string $aggrFunc
     * @return array
     */
    private function &_downSample(int $newStep, int $oldStep, string $aggrFunc): array
    {
        $newValues = [];
        $sum = null;
        $count = 0;
        $activeCount = 0;
        $thisTime = $this->getFrom()->getTimestamp(); // this is the time of the val at [0]
        foreach ($this->values as $value) {
            // TODO: simplify math to be a simple idx check... how?
            // reached the end of the grid
            if ((int)($thisTime / $newStep) * $newStep == $thisTime) {
                $newValues[] = ($sum === null) ? null :
                    (($aggrFunc == 'avg') ? $sum / $activeCount : $sum);
                $sum = null;
                $activeCount = 0;
                $count = 0;
            }
            if ($value !== null) {
                if ($sum === null) {
                    $sum = $value;
                    $activeCount = 1;
                } else {
                    $sum += $value;
                    $activeCount++;
                }
            }
            // prepare for the next value
            $count++;
            $thisTime += $oldStep;
        }
        // leftover values in final bin
        if ($count > 0) {
            $newValues[] = ($sum === null) ? null :
                (($aggrFunc == 'avg') ? $sum / $activeCount : $sum);
        }
        return $newValues;
    }

    /**
     * Down-samples the number of points in the series by approximately the
     * ratio given, using the given aggregation function.
     *
     * @param float $ratio
     * @param string $aggrFunc
     */
    public
    function downSample($ratio, $aggrFunc = 'avg')
    {
        $oldStep = $this->getStep();
        // decomposed into functions to find bottleneck
        $newStep = $this->findDownSampleStep($ratio, $oldStep);
        // sanity check. the ideal step must be a multiple of the original
        $reductionFactor = $newStep / $oldStep;
        // if it turns out that the ideal step is not a multiple of the original,
        // then just give up and don't downsample
        if (floor($reductionFactor) != $reductionFactor) {
            return;
        }
        $newValues = &$this->_downSample($newStep, $oldStep, $aggrFunc);
        // do we need to adjust the start time to align with this step?
        $shouldBeFrom = (floor($this->getFromEpoch() / $newStep) * $newStep);
        $this->setUntilEpoch($shouldBeFrom + (count($newValues) * $newStep));
        $this->setFromEpoch($shouldBeFrom);
        $this->values = &$newValues;
        $this->setStep($newStep);
    }
}
