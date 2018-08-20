<?php

namespace App\TimeSeries\Backend;


use App\Expression\AbstractExpression;
use App\TimeSeries\TimeSeriesSet;

class GraphiteBackend implements BackendInterface
{
    public function listQuery(AbstractExpression $expression): array
    {
        // TODO: Implement listQuery() method.
    }

    public function tsQuery(AbstractExpression $expression,
                            \DateTime $from, \DateTime $until): TimeSeriesSet
    {
        // TODO: Implement tsQuery() method.
    }
}
