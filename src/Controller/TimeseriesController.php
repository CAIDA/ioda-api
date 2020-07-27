<?php
/**
 * This software is Copyright (c) 2013 The Regents of the University of
 * California. All Rights Reserved. Permission to copy, modify, and distribute this
 * software and its documentation for academic research and education purposes,
 * without fee, and without a written agreement is hereby granted, provided that
 * the above copyright notice, this paragraph and the following three paragraphs
 * appear in all copies. Permission to make use of this software for other than
 * academic research and education purposes may be obtained by contacting:
 *
 * Office of Innovation and Commercialization
 * 9500 Gilman Drive, Mail Code 0910
 * University of California
 * La Jolla, CA 92093-0910
 * (858) 534-5815
 * invent@ucsd.edu
 *
 * This software program and documentation are copyrighted by The Regents of the
 * University of California. The software program and documentation are supplied
 * "as is", without any accompanying services from The Regents. The Regents does
 * not warrant that the operation of the program will be uninterrupted or
 * error-free. The end-user understands that the program was developed for research
 * purposes and is advised not to rely exclusively on the program for any reason.
 *
 * IN NO EVENT SHALL THE UNIVERSITY OF CALIFORNIA BE LIABLE TO ANY PARTY FOR
 * DIRECT, INDIRECT, SPECIAL, INCIDENTAL, OR CONSEQUENTIAL DAMAGES, INCLUDING LOST
 * PROFITS, ARISING OUT OF THE USE OF THIS SOFTWARE AND ITS DOCUMENTATION, EVEN IF
 * THE UNIVERSITY OF CALIFORNIA HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE. THE UNIVERSITY OF CALIFORNIA SPECIFICALLY DISCLAIMS ANY WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE. THE SOFTWARE PROVIDED HEREUNDER IS ON AN "AS
 * IS" BASIS, AND THE UNIVERSITY OF CALIFORNIA HAS NO OBLIGATIONS TO PROVIDE
 * MAINTENANCE, SUPPORT, UPDATES, ENHANCEMENTS, OR MODIFICATIONS.
 */

namespace App\Controller;

use App\Entity\Ioda\DatasourceEntity;
use App\Entity\Ioda\MetadataEntity;
use App\Expression\ExpressionFactory;
use App\Expression\ParsingException;
use App\Expression\PathExpression;
use App\Service\MetadataEntitiesService;
use App\Service\DatasourceService;
use App\Response\Envelope;
use App\Response\RequestParameter;
use App\TimeSeries\Backend\BackendException;
use App\TimeSeries\Backend\InfluxBackend;
use App\TimeSeries\Backend\GraphiteBackend;
use App\TimeSeries\TimeSeriesSet;
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

    private $influxService;

    private $dsToQuery;

    public function __construct(MetadataEntitiesService $metadataEntitiesService,
                                DatasourceService $datasourceService,
                                InfluxBackend $influxService
    ){
        $this->metadataService = $metadataEntitiesService;
        $this->datasourceService = $datasourceService;
        $this->influxService = $influxService;
        $this->dsToQuery = [
            "bgp" => "graphite",
            "ping-slash24" => "graphite",
            "ucsd-nt" => "influx",
        ];
    }

    private function validateUserInputs(QueryTime $from, QueryTime $until, ?string $datasource, $metas){
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

    /**
     * Build one graphite expression based on given $datasource_id.
     *
     * @param MetadataEntity $entity
     * @param DatasourceEntity $datasource_id
     * @return array
     */
    private function buildGraphiteExpression(MetadataEntity $entity, DatasourceEntity $datasource): array {

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
            // NOTE: "ucsd-nt" migrated to using influxdb
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

        return $queryJsons[$datasource->getDatasource()];
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
     *     type="integer",
     *     description="Unix timestamp from when the alerts should begin after",
     *     required=true
     * )
     * @SWG\Parameter(
     *     name="until",
     *     in="query",
     *     type="integer",
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
     * @SWG\Parameter(
     *     name="maxPoints",
     *     in="query",
     *     type="integer",
     *     description="Maximum number of points per time-series",
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
     * @param string|null $entityType
     * @param string|null $entityCode
     * @param ExpressionFactory $expressionFactory
     * @param GraphiteBackend $tsBackend
     * @return JsonResponse
     * @throws ParsingException
     * @var Request $request
     */
    public function lookup(
        ?string $entityType, ?string $entityCode, Request $request, ExpressionFactory $expressionFactory, GraphiteBackend $tsBackend)
    {
        $env = new Envelope('signals',
                            'query',
                            [
                                new RequestParameter('from', RequestParameter::INTEGER, null, true),
                                new RequestParameter('until', RequestParameter::INTEGER, null, true),
                                new RequestParameter('datasource', RequestParameter::STRING, null, false),
                                new RequestParameter('maxPoints', RequestParameter::INTEGER, null, false),
                                new RequestParameter('noinflux', RequestParameter::BOOL, false, false),
                            ],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        /* LOCAL PARAM PARSING */
        $from = $env->getParam('from');
        $until = $env->getParam('until');
        $datasource_str = $env->getParam('datasource');
        $maxPoints = $env->getParam('maxPoints');
        $noinflux = $env->getParam('noinflux');
        $metas = $this->metadataService->lookup($entityType, $entityCode);

        try{
            $from = new QueryTime($from);
            $until = new QueryTime($until);
            $this->validateUserInputs($from, $until, $datasource_str, $metas);
        } catch (\InvalidArgumentException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }

        // convert datasource id string to datasource objects array
        $datasource_array = [];
        if($datasource_str == null){
            $datasource_array = $this->datasourceService->getAllDatasources();
        } else {
            $datasource_array[] = $this->datasourceService->getDatasource($datasource_str);
        }

        // prepare TimeSeriesSet object
        $ts_set = new TimeSeriesSet();
        $ts_set->setMetadataEntity($metas[0]);

        // execute queries based on the datasources' defined backends
        try{
            $graphite_exps = [];
            $tses = [];

            foreach($datasource_array as $datasource){
                $backend = $datasource->getBackend();
                if($backend == "graphite"){
                    $exp_json = $this->buildGraphiteExpression($metas[0], $datasource);
                    $graphite_exps[] = $expressionFactory->createFromJson($exp_json);
                } else if ($backend=="influx"){
                    $tses[] = $this->influxService->getInfluxDataPoints($datasource, $metas[0], $from, $until, $maxPoints);
                } else {
                    throw new \InvalidArgumentException(
                        sprintf("invalid datasource %s (must be one of the following [%s])", $datasource, join(", ", $this->datasourceService->getDatasourceNames()))
                    );
                }
            }

            // if we have some graphite queries generated, we call the actual query
            if(count($graphite_exps)>0){
                $tses = array_merge($tses, $tsBackend->tsQuery($graphite_exps, $from, $until, $maxPoints));
            }

            // sanity-check each TimeSeries object and add it to the TimeSeriesSet
            foreach($tses as $ts){
                $ts->sanityCheckValues();
                $ts_set->addOneSeries($ts);
            }
        } catch (BackendException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }

        $env->setData($ts_set);
        return $this->json($env);
    }
}
