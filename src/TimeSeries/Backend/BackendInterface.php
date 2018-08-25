<?php

namespace App\TimeSeries\Backend;


use App\Expression\AbstractExpression;
use App\Expression\PathExpression;
use App\Timeseries\TimeSeriesSet;

interface BackendInterface
{
    /**
     * Perform a metadata query to identify paths that match the given path
     * expression.
     *
     * @param PathExpression $expression
     * @param bool $absolute_paths
     *
     * @return PathExpression[]
     * @throws BackendException
     */
    public function pathListQuery(PathExpression $expression,
                                  bool $absolute_paths): array;

    /**
     * Perform a query for time series data.
     *
     * @param AbstractExpression $expression
     * @param \DateTime $from
     * @param \DateTime $until
     * @param string $aggrFunc
     * TODO: bool $annotate
     *
     * @return TimeSeriesSet
     * @throws BackendException
     */
    public function tsQuery(AbstractExpression $expression,
                            \DateTime $from, \DateTime $until,
                            string $aggrFunc): TimeSeriesSet;
}
