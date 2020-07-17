<?php
namespace App\Entity\Outages;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class OutagesEventNormalizer implements ContextAwareNormalizerInterface
{
    private $router;
    private $normalizer;

    public function __construct(UrlGeneratorInterface $router, ObjectNormalizer $normalizer)
    {
        $this->router = $router;
        $this->normalizer = $normalizer;
    }

    public function normalize($event, $format = null, array $context = [])
    {

        $data = $this->normalizer->normalize($event, $format, $context);
        $res = [];
        if($event->getFormat()=="codf"){

            $res["location"] = sprintf("%s/%s", $data["entity"]["type"], $data["entity"]["code"]);
            $res["start"] = $event->getFrom();
            $res["duration"] = $event->getUntil() - $event->getFrom();
            $res["uncertainty"] = null;
            $res["status"] = 0;
            $res["fraction"] = null;
            $res["score"] = $event->getScore();
            $res["location_name"] = $data["entity"]['name'];
            $res["overlaps_window"] = $event->isOverlap();

        } elseif ($event->getFormat()=="ioda"){
            if(!$event->isIncludeAlerts()){
                unset($data['alerts']);
            }
            if($data['from']==0){
                unset($data['from']);
            }
            if($data['until']==0){
                unset($data['until']);
            }
            $res = $data;
        } else {

        }

        return $res;
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof OutagesEvent;
    }
}
