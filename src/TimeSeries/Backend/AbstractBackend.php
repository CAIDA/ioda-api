<?php

namespace App\TimeSeries\Backend;


use App\Expression\AbstractExpression;
use App\Expression\PathExpression;
use App\Timeseries\TimeSeriesSet;
use App\Utils\QueryTime;

abstract class AbstractBackend
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
    abstract public function pathListQuery(PathExpression $expression,
                                           bool $absolute_paths): array;

    /**
     * Perform a query for time series data.
     *
     * @param AbstractExpression[] $expressions
     * @param QueryTime $from
     * @param QueryTime $until
     * @param string $aggrFunc
     * @param bool $annotate
     *
     * @return TimeSeriesSet
     * @throws BackendException
     */
    abstract public function tsQuery(array $expressions,
                                     QueryTime $from, QueryTime $until,
                                     string $aggrFunc,
                                     bool $annotate): TimeSeriesSet;
}
