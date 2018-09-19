<?php


namespace App\TimeSeries\Annotation\Provider;


use App\Expression\AbstractExpression;
use App\TimeSeries\Annotation\AbstractAnnotation;
use App\TimeSeries\TimeSeriesSummary;

abstract class AbstractAnnotationProvider
{
    /**
     * Possibly annotate the given series with meta-data
     *
     * @param AbstractExpression $expression
     * @param TimeSeriesSummary $summary
     * @return AbstractAnnotation[]
     */
    public abstract function annotateExpression(AbstractExpression $expression,
                                                TimeSeriesSummary $summary = null): array;
}
