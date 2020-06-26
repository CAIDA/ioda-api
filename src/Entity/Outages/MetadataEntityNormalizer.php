<?php
namespace App\Entity\Outages;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MetadataEntityNormalizer implements ContextAwareNormalizerInterface
{
    private $router;
    private $normalizer;

    public function __construct(UrlGeneratorInterface $router, ObjectNormalizer $normalizer)
    {
        $this->router = $router;
        $this->normalizer = $normalizer;
    }

    public function normalize($entity, $format = null, array $context = [])
    {
        $data = $this->normalizer->normalize($entity, $format, $context);
        $data["type"] = $data["type"]["type"];
        $data["attrs"] = new \ArrayObject();
        foreach($data["attributes"] as $d){
            $data["attrs"][$d["key"]] = $d["value"];
        }
        unset($data["attributes"]);

        return $data;
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof MetadataEntity;
    }
}
