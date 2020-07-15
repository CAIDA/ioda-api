<?php
namespace App\TimeSeries;

use App\TimeSeries\TimeSeriesSet;
use App\TimeSeries\TimeSeriesNormalizer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class TimeSeriesNormalizer implements ContextAwareNormalizerInterface
{
    private $router;
    private $normalizer;

    public function __construct(UrlGeneratorInterface $router, ObjectNormalizer $normalizer)
    {
        $this->router = $router;
        $this->normalizer = $normalizer;
    }

    public function normalize($timeSeries, $format = null, array $context = [])
    {
        $data = [];
        $data["datasource"] = $timeSeries->getDatasource();
        $data["from"] = $timeSeries->getFrom()->getTimestamp();
        $data["until"] = $timeSeries->getUntil()->getTimestamp();
        $data["step"] = $timeSeries->getStep();
        $data["nativeStep"] = $timeSeries->getNativeStep();
        $data["values"] = $timeSeries->getValues();
        $entity = $this->normalizer->normalize($timeSeries->getMetadataEntity(), $format, $context);
        $data["entityType"] = $entity["type"]["type"];
        $data["entityCode"] = $entity["code"];


        return $data;
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof TimeSeries;
    }
}
