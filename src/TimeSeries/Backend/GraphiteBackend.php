<?php

namespace App\TimeSeries\Backend;


use App\Expression\AbstractExpression;
use App\Expression\PathExpression;
use App\TimeSeries\TimeSeriesSet;

class GraphiteBackend implements BackendInterface
{
    public function pathListQuery(PathExpression $pathExp,
                                  bool $absolute_paths): array
    {
        return [$pathExp];
    }

    public function tsQuery(AbstractExpression $expression,
                            \DateTime $from, \DateTime $until): TimeSeriesSet
    {
        // TODO: Implement tsQuery() method.
    }
}
