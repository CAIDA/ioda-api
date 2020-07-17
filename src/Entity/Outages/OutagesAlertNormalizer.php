<?php
namespace App\Entity\Outages;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class OutagesAlertNormalizer implements ContextAwareNormalizerInterface
{
    private $router;
    private $normalizer;

    public function __construct(UrlGeneratorInterface $router, ObjectNormalizer $normalizer)
    {
        $this->router = $router;
        $this->normalizer = $normalizer;
    }

    public function normalize($alert, $format = null, array $context = [])
    {
        $data = $this->normalizer->normalize($alert, $format, $context);
        $res = array();
        $res["datasource"]=$data["datasource"];
        $res["entity"] = $data["entity"];
        $res["time"] = $data["time"];
        $res["level"] = $data["level"];
        $res["condition"] = $data["condition"];
        $res["value"] = $data["value"];
        $res["historyValue"] = $data["historyValue"];

        return $res;
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof OutagesAlert;
    }
}
