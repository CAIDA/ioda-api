<?php


namespace App\TimeSeries\Annotation;


use App\Expression\AbstractExpression;
use App\TimeSeries\Annotation\Provider\AbstractAnnotationProvider;
use App\TimeSeries\Annotation\Provider\AsnAnnotationProvider;
use App\TimeSeries\Annotation\Provider\GeoAnnotationProvider;
use App\TimeSeries\TimeSeriesSummary;

class AnnotationFactory
{
    /**
     * Possibly annotate a given AbstractExpression with meta data.
     * Checks each available annotation provider.
     *
     * @param AbstractExpression $expression
     * @param TimeSeriesSummary $summary
     * @return AbstractAnnotation[]|null
     */
    public static function annotateExpression(AbstractExpression $expression,
                                              $summary = null): ?array
    {
        // TODO find smart way to specify which providers should be searched
        $providers = [
            new GeoAnnotationProvider(),
            new AsnAnnotationProvider(),
        ];
        $anns = [];
        // for each of the annotation providers that we have, check for metadata
        foreach ($providers as $provider) {
            /* @var $provider AbstractAnnotationProvider */
            $anns = array_merge($anns,
                                $provider->annotateExpression($expression,
                                                              $summary));
        }
        if (!count($anns)) {
            // TODO: is this really what we want to do?
            return null;
        }
        return $anns;
    }
}
