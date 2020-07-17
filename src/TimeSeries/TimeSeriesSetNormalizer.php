<?php
namespace App\TimeSeries;

use App\TimeSeries\TimeSeriesSet;
use App\TimeSeries\TimeSeriesNormalizer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class TimeSeriesSetNormalizer implements ContextAwareNormalizerInterface
{
    private $router;
    private $normalizer;

    public function __construct(UrlGeneratorInterface $router, TimeSeriesNormalizer $normalizer)
    {
        $this->router = $router;
        $this->normalizer = $normalizer;
    }

    public function normalize($timeSeriesSet, $format = null, array $context = [])
    {
        $normalized = [];
        foreach($timeSeriesSet->getSeries() as $datasource => $series){
           $series->setDatasource($datasource);
           $series->setMetadataEntity($timeSeriesSet->getMetadataEntity());
           $normalized[] = $this->normalizer->normalize($series, $format, $context);
        }
        return $normalized;
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof TimeSeriesSet;
    }
}
