<?php

namespace App\Controller;

use App\Service\MetadataEntitiesService;
use App\Service\OutagesAlertsService;
use App\Service\OutagesEventsService;
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
     * @Route("/events/{entityType}/{entityCode}", methods={"GET"}, name="events", defaults={"entityType"=null,"entityCode"=null})
     *
     * @var string|null $entityType
     * @var string|null $entityCode
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var OutagesEventsService
     * @return JsonResponse
     */
    public function events(
        ?string $entityType, ?string $entityCode,
        Request $request,
        SerializerInterface $serializer,
        OutagesEventsService $eventsService,
        OutagesAlertsService $alertsService
    ){
        $env = new Envelope('outages.events',
            'query',
            [
                new RequestParameter('from', RequestParameter::STRING, null, true),
                new RequestParameter('until', RequestParameter::STRING, null, true),
                new RequestParameter('datasource', RequestParameter::STRING, null, false),
                new RequestParameter('includeAlerts', RequestParameter::BOOL, false, false),
                new RequestParameter('format', RequestParameter::STRING, "codf", false),
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
        $includeAlerts = $env->getParam('includeAlerts');
        $format = $env->getParam('format');
        $limit = $env->getParam('limit');
        $page = $env->getParam('page');

        $alerts = $alertsService->findAlerts($from, $until, $entityType, $entityCode, $datasource);

        $events = $eventsService->buildEventsSimple($alerts, $includeAlerts, $format, $from, $until, $limit, $page);

        $env->setData($events);
        return $this->json($env);
    }

    /**
     * @Route("/summary/{entityType}/{entityCode}", methods={"GET"}, name="summary", defaults={"entityType"=null,"entityCode"=null})
     *
     * @var string|null $entityType
     * @var string|null $entityCode
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var OutagesEventsService
     * @return JsonResponse
     */
    public function summary(
        ?string $entityType, ?string $entityCode,
        Request $request,
        SerializerInterface $serializer,
        OutagesEventsService $eventsService,
        OutagesAlertsService $alertsService
    ){
        $env = new Envelope('outages.summary',
            'query',
            [
                new RequestParameter('from', RequestParameter::STRING, null, true),
                new RequestParameter('until', RequestParameter::STRING, null, true),
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

        $limit = $env->getParam('limit');
        $page = $env->getParam('page');

        $alerts = $alertsService->findAlerts($from, $until, $entityType, $entityCode, null);

        $events = $eventsService->buildEventsSummary($alerts, $from, $until, $limit, $page);

        $env->setData($events);
        return $this->json($env);
    }
}
