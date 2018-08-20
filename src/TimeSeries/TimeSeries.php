<?php

namespace App\TimeSeries;


use App\Expression\AbstractExpression;

class TimeSeries
{

    /**
     * Expression that resulted in this time series.
     *
     * @var AbstractExpression
     */
    protected $expression;

    /**
     * Time of the first data point in this time series.
     *
     * @var \DateTime
     */
    protected $from;

    /**
     * Time after the last data point in this time series.
     * I.e., last_data_point + step.
     *
     * @var \DateTime
     */
    protected $until;

    /**
     * Time (in seconds) between data points in this time series (after any
     * down-sampling).
     *
     * @var integer
     */
    protected $step;

    /**
     * Time (in seconds) between data points in the original time series (before
     * any down-sampling).
     *
     * @var integer
     */
    protected $nativeStep;

    /**
     * Array of time series data points. The first point is at $from, each point
     * is $step seconds apart, and the last point is at ($until-$from).
     *
     * @var integer[]
     */
    protected $values;

    /**
     * TODO: here
     * @var
     */
    protected $annotations;
}
