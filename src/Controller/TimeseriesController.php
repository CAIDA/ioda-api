<?php

namespace App\Controller;

use App\Expression\ExpressionFactory;
use App\Expression\ParsingException;
use App\Expression\PathExpression;
use App\Service\MetadataEntitiesService;
use App\Service\DatasourceService;
use App\Response\Envelope;
use App\Response\RequestParameter;
use App\TimeSeries\Backend\BackendException;
use App\TimeSeries\Backend\GraphiteBackend;
use App\TimeSeries\Humanize\Humanizer;
use App\Utils\QueryTime;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class TimeseriesController
 * @package App\Controller\Timeseries
 * @Route("/signals", name="signals_")
 */
class TimeseriesController extends ApiController
{

    /**
     * @var MetadataEntitiesService
     */
    private $metadataService;

    private $datasourceService;

    public function __construct(MetadataEntitiesService $metadataEntitiesService, DatasourceService $datasourceService){
        $this->metadataService = $metadataEntitiesService;
        $this->datasourceService = $datasourceService;
    }

    private function sanitizeInputs(&$from, &$until, $datasource, $metas){
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

        $from = new QueryTime($from);
        $until = new QueryTime($until);

        if ($from->getEpochTime() > $until->getEpochTime()){
            throw new \InvalidArgumentException(
                sprintf("from (%d) must be earlier than until (%d)", $from->getEpochTime(), $until->getEpochTime())
            );
        }

        if ($datasource && !$this->datasourceService->isValidDatasource($datasource)){
            throw new \InvalidArgumentException(
                sprintf("invalid datasource %s (must be one of the following [%s])", $datasource, join(", ", $this->datasourceService->getDatasourceNames()))
            );
        }

        if(count($metas)!=1){
            throw new \InvalidArgumentException(
                sprintf("cannot find corresponding metadata entity")
            );
        }
    }

    private function buildExpression($entity, $datasource){
        $fqid = $entity->getAttribute("fqid");
        $queryJsons = [
            "bgp" => [
                "type" => "function",
                "func" => "alias",
                "args" => [
                    [
                        "type"=> "path",
                        "path"=> sprintf("bgp.prefix-visibility.%s.v4.visibility_threshold.min_50%%_ff_peer_asns.visible_slash24_cnt",$fqid)
                    ],
                    [
                        "type"=> "constant",
                        "value"=> "bgp"
                    ]
                ]
            ],
            "ucsd-nt" => [
                "type" => "function",
                "func" => "alias",
                "args" => [
                    [
                        "type" => "path",
                        "path" => sprintf("darknet.ucsd-nt.non-erratic.%s.uniq_src_ip", $fqid)
                    ],
                    [
                        "type" => "constant",
                        "value" => "ucsd-nt"
                    ]
                ]
            ],
            "ping-slash24" => [
                "type" => "function",
                "func" => "alias",
                "args" => [
                    [
                        "type" => "function",
                        "func" => "sumSeries",
                        "args" => [
                            [
                                "type" => "function",
                                "func" => "keepLastValue",
                                "args" => [
                                    [
                                        "type" => "path",
                                        "path" => "active.ping-slash24.geo.netacuity.NA.KN.probers.team-1.caida-sdsc.*.up_slash24_cnt"
                                    ], [
                                        "type" => "constant",
                                        "value" => 1
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        "type" => "constant",
                        "value" => "ping-slash24"
                    ]
                ]
            ],
        ];

        if(!$datasource){
            return array_values($queryJsons);
        } else {
            if(!array_key_exists($datasource, $queryJsons)){
                throw new \InvalidArgumentException(
                    sprintf("Unknown datasource %s, must be one of [%s]", $datasource,join(", ",array_keys($queryJsons)))
                );
            } else {
                return [$queryJsons[$datasource]];
            }
        }
    }

    /**
     * Retrieve time-series signals.
     *
     * @Route("/{entityType}/{entityCode}", methods={"GET"}, name="get")
     * @SWG\Tag(name="Time Series Signals")
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
     *     description="Unix timestamp until when the alerts should end before",
     *     required=true
     * )
     * @SWG\Parameter(
     *     name="datasource",
     *     in="query",
     *     type="string",
     *     description="Filter signals by datasource",
     *     enum={"bgp", "ucsd-nt", "ping-slash24"},
     *     required=false,
     *     default=null
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Return time series signals",
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
     *                              property="entityType",
     *                              type="string"
     *                          ),
     *                          @SWG\Property(
     *                              property="entityCode",
     *                              type="string"
     *                          ),
     *                          @SWG\Property(
     *                              property="datasource",
     *                              type="string"
     *                          ),
     *                          @SWG\Property(
     *                              property="from",
     *                              type="number"
     *                          ),
     *                          @SWG\Property(
     *                              property="until",
     *                              type="number"
     *                          ),
     *                          @SWG\Property(
     *                              property="step",
     *                              type="number"
     *                          ),
     *                          @SWG\Property(
     *                              property="nativeStep",
     *                              type="number"
     *                          ),
     *                          @SWG\Property(
     *                              property="values",
     *                              type="array",
     *                              @SWG\Items(
     *                                  type="integer"
     *                              )
     *                          )
     *                     )
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @var string $entityType
     * @var string $entityCode
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var MetadataEntitiesService
     * @return JsonResponse
     */
    public function lookup(
        ?string $entityType, ?string $entityCode,
        Request $request,
        SerializerInterface $serializer,
        ExpressionFactory $expressionFactory,
        GraphiteBackend $tsBackend)
    {
        $env = new Envelope('signals',
                            'query',
                            [
                                new RequestParameter('from', RequestParameter::STRING, null, true),
                                new RequestParameter('until', RequestParameter::STRING, null, true),
                                new RequestParameter('datasource', RequestParameter::STRING, null, false),
                                new RequestParameter('maxPoints', RequestParameter::INTEGER, null, false),
                            ],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        /* LOCAL PARAM PARSING */
        $from = $env->getParam('from');
        $until = $env->getParam('until');
        $datasource = $env->getParam('datasource');
        $maxPoints = $env->getParam('maxPoints');
        $metas = $this->metadataService->lookup($entityType, $entityCode);

        try{
            $this->sanitizeInputs($from, $until, $datasource, $metas);
        } catch (\InvalidArgumentException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }


        /* BUILD EXPRESSIONS BASED ON ENTITY TYPE AND CODE */
        $jsons = $this->buildExpression($metas[0], $datasource);
        $exps = [];
        foreach($jsons as &$json){
            $exps[] = $expressionFactory->createFromJson($json);
        }

        /* QUERY TIMESERIES GRAPHITE BACKEND */
        try {
            $tss = $tsBackend->tsQuery(
                $exps,
                $from,
                $until,
                'avg',   // aggrFunc
                false,  // annotate
                true,   // adaptiveDownsampling
                false   // checkPathWhitelist
            );
        } catch (BackendException $ex) {
            $env->setError($ex->getMessage());
            // TODO: check HTTP error codes used
            //
            return $this->json($env, 400);
        }

        // TODO: sanity check timeseries data points
        $this->dataSanityCheck($tss);
        $tss->setMetadataEntity($metas[0]);
        $env->setData($tss);
        // $env->setData(array_values($tss->getSeries()));
        return $this->json($env);
    }

    private function dataSanityCheck($tss){
        foreach($tss->getSeries() as $datasource => $ts){
            $step = $ts->getStep();
            $values = $ts->getValues();

            $from = $ts->getFrom()->getTimestamp();
            $until = $ts->getUntil()->getTimestamp();
            if(count($values)>0 && ($until-$from)/($step) != count($values)){
                throw new \InvalidArgumentException(
                    sprintf("wrong values count %d != (%d - %d) / %d", count($values), $until, $from, $step)
                );
            }
        }
    }
}
