<?php

namespace App\Controller;

use Swagger\Annotations as SWG;
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
     * Retrieve IODA outage alerts.
     *
     * @Route("/alerts/{entityType}/{entityCode}", methods={"GET"}, name="alerts", defaults={"entityType"=null,"entityCode"=null})
     * @SWG\Tag(name="Outages")
     * @SWG\Parameter(
     *     name="entityType",
     *     in="path",
     *     type="string",
     *     description="Type of the entity, e.g. country, region, asn",
     *     enum={"continent", "country", "region", "county", "asn"},
     *     required=false,
     *     default=null
     * )
     * @SWG\Parameter(
     *     name="entityCode",
     *     in="path",
     *     type="string",
     *     description="Code of the entity, e.g. for United States the code is 'US'",
     *     required=false,
     *     default=null
     * )
     * @SWG\Parameter(
     *     name="from",
     *     in="query",
     *     type="string",
     *     description="Unix timestamp from when the alerts should begin after",
     *     required=true
     * )
     * @SWG\Parameter(
     *     name="until",
     *     in="query",
     *     type="string",
     *     description="Unix timestamp until when the alerts should begin after",
     *     required=true
     * )
     * @SWG\Parameter(
     *     name="datasource",
     *     in="query",
     *     type="string",
     *     description="Filter alerts by datasource",
     *     enum={"bgp", "ucsd-nt", "ping-slash24"},
     *     required=false
     * )
     * @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     type="integer",
     *     description="Maximum number of alerts this query returns",
     *     required=false
     * )
     * @SWG\Parameter(
     *     name="page",
     *     in="query",
     *     type="integer",
     *     description="Page number of the alerts",
     *     required=false
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Return an array of all data sources used by IODA",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"outages.alerts"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="array",
     *                     @SWG\Items(
     *                          @SWG\Property(
     *                              property="datasource",
     *                              type="string"
     *                          ),
     *                          @SWG\Property(
     *                              property="entity",
     *                              ref=@Model(type=\App\Entity\Ioda\MetadataEntity::class, groups={"public"})
     *                          ),
     *                          @SWG\Property(
     *                              property="time",
     *                              type="integer",
     *                          ),
     *                          @SWG\Property(
     *                              property="level",
     *                              type="string",
     *                          ),
     *                          @SWG\Property(
     *                              property="condition",
     *                              type="string",
     *                          ),
     *                          @SWG\Property(
     *                              property="value",
     *                              type="integer",
     *                          ),
     *                          @SWG\Property(
     *                              property="historicValue",
     *                              type="integer",
     *                          )
     *                     )
     *                 )
     *             )
     *         }
     *     )
     * )
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
     * Retrieve IODA outage events.
     *
     * @Route("/events/{entityType}/{entityCode}", methods={"GET"}, name="events", defaults={"entityType"=null,"entityCode"=null})
     * @SWG\Tag(name="Outages")
     * @SWG\Parameter(
     *     name="entityType",
     *     in="path",
     *     type="string",
     *     description="Type of the entity, e.g. country, region, asn",
     *     enum={"continent", "country", "region", "county", "asn"},
     *     required=false,
     *     default=null
     * )
     * @SWG\Parameter(
     *     name="entityCode",
     *     in="path",
     *     type="string",
     *     description="Code of the entity, e.g. for United States the code is 'US'",
     *     required=false,
     *     default=null
     * )
     * @SWG\Parameter(
     *     name="from",
     *     in="query",
     *     type="string",
     *     description="Unix timestamp from when the alerts should begin after",
     *     required=true
     * )
     * @SWG\Parameter(
     *     name="until",
     *     in="query",
     *     type="string",
     *     description="Unix timestamp until when the alerts should begin after",
     *     required=true
     * )
     * @SWG\Parameter(
     *     name="includeAlerts",
     *     in="query",
     *     type="boolean",
     *     description="Whether to include alerts in the returned events",
     *     default=false,
     *     required=false
     * )
     * @SWG\Parameter(
     *     name="format",
     *     in="path",
     *     type="string",
     *     description="Returned event object format",
     *     enum={"codf", "ioda"},
     *     required=false,
     *     default="codf"
     * )
     * @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     type="integer",
     *     description="Maximum number of events this query returns",
     *     required=false
     * )
     * @SWG\Parameter(
     *     name="page",
     *     in="query",
     *     type="integer",
     *     description="Page number of the events",
     *     required=false
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Return an array of outage events generated by IODA",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"outages.alerts"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="array",
     *                     @SWG\Items(
     *                          @SWG\Property(
     *                              property="location",
     *                              type="string"
     *                          ),
     *                          @SWG\Property(
     *                              property="location_name",
     *                              type="string"
     *                          ),
     *                          @SWG\Property(
     *                              property="start",
     *                              type="integer",
     *                          ),
     *                          @SWG\Property(
     *                              property="duration",
     *                              type="integer",
     *                          ),
     *                          @SWG\Property(
     *                              property="uncertainty",
     *                              type="string",
     *                          ),
     *                          @SWG\Property(
     *                              property="status",
     *                              type="integer",
     *                          ),
     *                          @SWG\Property(
     *                              property="fraction",
     *                              type="string",
     *                          ),
     *                          @SWG\Property(
     *                              property="score",
     *                              type="float",
     *                          ),
     *                          @SWG\Property(
     *                              property="overlaps_window",
     *                              type="boolean",
     *                          )
     *                     )
     *                 )
     *             )
     *         }
     *     )
     * )
     * TODO: figure out how to document ioda format here as well
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
     * Retrieve IODA outage summaries.
     *
     * @Route("/summary/{entityType}/{entityCode}", methods={"GET"}, name="summary", defaults={"entityType"=null,"entityCode"=null})
     * @SWG\Tag(name="Outages")
     * @SWG\Parameter(
     *     name="entityType",
     *     in="path",
     *     type="string",
     *     description="Type of the entity, e.g. country, region, asn",
     *     enum={"continent", "country", "region", "county", "asn"},
     *     required=false,
     *     default=null
     * )
     * @SWG\Parameter(
     *     name="entityCode",
     *     in="path",
     *     type="string",
     *     description="Code of the entity, e.g. for United States the code is 'US'",
     *     required=false,
     *     default=null
     * )
     * @SWG\Parameter(
     *     name="from",
     *     in="query",
     *     type="string",
     *     description="Unix timestamp from when the alerts should begin after",
     *     required=true
     * )
     * @SWG\Parameter(
     *     name="until",
     *     in="query",
     *     type="string",
     *     description="Unix timestamp until when the alerts should begin after",
     *     required=true
     * )
     * @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     type="integer",
     *     description="Maximum number of entity summaries this query returns",
     *     required=false
     * )
     * @SWG\Parameter(
     *     name="page",
     *     in="query",
     *     type="integer",
     *     description="Page number of the summaries",
     *     required=false
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Return an array of outage summaries for entities generated by IODA",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"outages.alerts"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="array",
     *                     @SWG\Items(
     *                          @SWG\Property(
     *                              property="scores",
     *                              type="object",
     *                              @SWG\Property(
     *                                  property="overall",
     *                                  type="number"
     *                              ),
     *                              @SWG\Property(
     *                                  property="bgp",
     *                                  type="number"
     *                              ),
     *                              @SWG\Property(
     *                                  property="ucsd-nt",
     *                                  type="number"
     *                              ),
     *                              @SWG\Property(
     *                                  property="ping-slash24",
     *                                  type="number"
     *                              ),
     *                          ),
     *                          @SWG\Property(
     *                              property="entity",
     *                              ref=@Model(type=\App\Entity\Ioda\MetadataEntity::class, groups={"public"})
     *                          )
     *                     )
     *                 )
     *             )
     *         }
     *     )
     * )
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
