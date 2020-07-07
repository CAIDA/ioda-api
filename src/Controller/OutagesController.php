<?php

namespace App\Controller;

use App\MetadataEntities\MetadataEntitiesService;
use App\Outages\OutagesAlertsService;
use App\Response\Envelope;
use App\Response\RequestParameter;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class EntitiesController
 * @package App\Controller
 * @Route("/outages", name="outages_")
 */
class OutagesController extends ApiController
{
    private function parseTimestampParameter($text){
        if (ctype_digit($text)) { // text is positive integer
            if ($text <= '2147483647') { // for sql Integer type
                return intval($text);
            }
        }
        return null;
    }
    /**
     * @Route("/alerts/{entityType}/{entityCode}", methods={"GET"}, name="alerts", defaults={"entityType"=null,"entityCode"=null})
     *
     * @var string|null $entityType
     * @var string|null $entityCode
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var MetadataEntitiesService
     * @return JsonResponse
     */
    public function alerts(
        ?string $entityType, ?string $entityCode,
        Request $request,
        SerializerInterface $serializer,
        OutagesAlertsService $service
    ){
        $env = new Envelope('outages.alerts',
            'query',
            [
                new RequestParameter('from', RequestParameter::STRING, null, true),
                new RequestParameter('until', RequestParameter::STRING, null, true),
                new RequestParameter('datasource', RequestParameter::STRING, null, false),
                new RequestParameter('limit', RequestParameter::INTEGER, null, false),
                new RequestParameter('page', RequestParameter::INTEGER, null, false),
            ],
            $request
        );

        /* LOCAL PARAM PARSING */
        $from = $this->parseTimestampParameter($env->getParam('from'));
        $until = $this->parseTimestampParameter($env->getParam('until'));
        if(!isset($from)){
            throw new \InvalidArgumentException(
                "'from' timestamp must be set"
            );
        }
        if(!isset($until)){
            throw new \InvalidArgumentException(
                "'until' timestamp must be set"
            );
        }

        $datasource = $env->getParam('datasource');
        $limit = $env->getParam('limit');
        $page = $env->getParam('page');

        $alerts = $service->findAlerts($from, $until, $entityType, $entityCode, $datasource, $limit, $page);

        // $env->setData(array([$entityType, $entityCode, $from, $until, $datasource, $limit, $page]));
        $env->setData($alerts);
        return $this->json($env);
    }

    /**
     * @Route("/events/{entityType}/{entityCode}", methods={"GET"}, name="events")
     *
     * @var string|null $entityType
     * @var string|null $entityCode
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var MetadataEntitiesService
     * @return JsonResponse
     */
    public function events(
        ?string $entityType, ?string $entityCode,
        Request $request,
        SerializerInterface $serializer,
        MetadataEntitiesService $service
    ){
        $env = new Envelope('outages.alerts',
            'query',
            [
                new RequestParameter('from', RequestParameter::INTEGER, null, true),
                new RequestParameter('until', RequestParameter::INTEGER, null, true),
                new RequestParameter('limit', RequestParameter::INTEGER, null, false),
                new RequestParameter('page', RequestParameter::INTEGER, null, false),
                new RequestParameter('datasource', RequestParameter::STRING, null, false),
                new RequestParameter('includeAlerts', RequestParameter::BOOL, null, false),
                new RequestParameter('format', RequestParameter::STRING, "codf", false),
            ],
            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        /* LOCAL PARAM PARSING */
        $search = $env->getParam('search');
        $relatedTo = $env->getParam('relatedTo');
        $limit = $env->getParam('limit');
        if($search){
            $entity = $service->search($entityType, null, $search, $limit, true);
            $env->setData($entity);
            return $this->json($env);
        }

        if ($relatedTo) {
            $relatedTo = explode('/', $relatedTo);
            if (count($relatedTo) > 2) {
                throw new \InvalidArgumentException(
                    "relatedTo parameter must be in the form 'type[/code]'"
                );
            }
            if (count($relatedTo) == 1) {
                $relatedTo[] = null;
            }
        } else {
            $relatedTo = [null, null];
        }

        $entity = $service->lookup($entityType, $entityCode, $relatedTo[0], $relatedTo[1], $limit);
        $env->setData($entity);
        return $this->json($env);
    }
}
