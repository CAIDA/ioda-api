<?php

namespace App\TimeSeries\Backend;


use App\Expression\AbstractExpression;
use App\Timeseries\TimeSeriesSet;

interface BackendInterface
{
    /**
     * Perform a metadata query to identify time series that match the given
     * expression.
     *
     * @param AbstractExpression $expression
     * @param bool $absolute_paths
     * @param bool $include_ranges
     *
     * @return AbstractExpression[]
     */
    public function listQuery(AbstractExpression $expression,
                              bool $absolute_paths,
                              bool $include_ranges): array;

    /**
     * Perform a query for time series data.
     *
     * @param AbstractExpression $expression
     * @param \DateTime $from
     * @param \DateTime $until
     * TODO: aggrFunc (pass callback?)
     * TODO: bool $annotate
     *
     * @return TimeSeriesSet
     */
    public function tsQuery(AbstractExpression $expression,
                            \DateTime $from, \DateTime $until): TimeSeriesSet;
}
