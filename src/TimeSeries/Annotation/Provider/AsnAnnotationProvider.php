<?php


namespace App\TimeSeries\Annotation\Provider;


use App\Expression\AbstractExpression;
use App\Expression\PathExpression;
use App\TimeSeries\Annotation\AbstractAnnotation;
use App\TimeSeries\Annotation\AsnMetaAnnotation;
use App\TimeSeries\TimeSeriesSummary;

class AsnAnnotationProvider extends AbstractAnnotationProvider
{
    /**
     * @param string $pe
     * @return AbstractAnnotation[]
     */
    private function getAnnotationsForPath(string $pe): array
    {
        $sep = preg_quote(PathExpression::SEPARATOR, '/');
        $regex = '/' . $sep . 'asn' . $sep . '(\d+)' . $sep . '/';
        if (preg_match($regex, $pe, $matches)) {
            // TODO: add extra metadata to ASN info (name, etc)
            $asn = $matches[1];
            return [new AsnMetaAnnotation('asn.' . $asn, $asn, 'AS' . $asn)];
        }
        return [];
    }

    public function annotateExpression(AbstractExpression $expression,
                                       TimeSeriesSummary $summary = null): array
    {
        // look through all PathExpressions in this expression and build
        // annotations for each. if all path expressions have the same set of
        // annotations, then annotate the overall expression, otherwise, nope.
        $anns = [];
        /** @var PathExpression $pe */
        foreach ($expression->getAllByType('path') as $pe) {
            $thisAnns = $this->getAnnotationsForPath($pe->getPath());
            if (!count($anns)) {
                $anns = $thisAnns;
            } else {
                if (count(array_intersect($anns, $thisAnns)) != count($anns)) {
                    return [];
                }
            }
        }
        return $anns;
    }
}
