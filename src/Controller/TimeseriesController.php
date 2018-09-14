<?php

namespace App\Controller;

use App\Expression\ExpressionFactory;
use App\Expression\ParsingException;
use App\Expression\PathExpression;
use App\Response\Envelope;
use App\Response\RequestParameter;
use App\TimeSeries\Backend\BackendException;
use App\TimeSeries\Backend\GraphiteBackend;
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
 * @Route("/ts", name="ts_")
 */
class TimeseriesController extends ApiController
{
    /**
     * Perform a query for time series data
     *
     * @Route("/query/", methods={"POST"}, name="query")
     * @SWG\Tag(name="Time Series")
     * @SWG\Parameter(
     *     name="query",
     *     in="body",
     *     type="object",
     *     description="Query object. Due to limitations in the current API documentation, the full expression schema cannot be properly described. See the various `*Expression` model definitions for more information about types of supported expressions.",
     *     required=true,
     *     @SWG\Schema(
     *         @SWG\Property(
     *             property="expression",
     *             type="object",
     *             description="JSON-encoded expression object.",
     *             ref=@Model(type=\App\Expression\AbstractExpression::class, groups={"public"})
     *        ),
     *        @SWG\Property(
     *             property="from",
     *             type="string",
     *             description="Start time of the query (inclusive). Times can be either absolute (e.g., '2018-08-31T16:08:18Z') or relative (e.g. '-24h')",
     *        ),
     *        @SWG\Property(
     *             property="until",
     *             type="string",
     *             description="End time of the query (exclusive). Times can be either absolute (e.g., '2018-08-31T16:08:18Z') or relative (e.g. '-24h')",
     *        ),
     *        @SWG\Property(
     *             property="aggregation_func",
     *             type="string",
     *             default="avg",
     *             enum={"avg", "sum"},
     *             description="Aggregation function to use when down-sampling data points",
     *        ),
     *     ),
     *     @SWG\Schema(ref=@Model(type=\App\Expression\PathExpression::class, groups={"public"})),
     *     @SWG\Schema(ref=@Model(type=\App\Expression\ConstantExpression::class, groups={"public"})),
     *     @SWG\Schema(ref=@Model(type=\App\Expression\FunctionExpression::class, groups={"public"}))
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Contains an array of time series that matched the given query.",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"ts.query"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="array",
     *                     items=@SWG\Schema(ref=@Model(type=\App\TimeSeries\TimeSeries::class, groups={"public"}))
     *                 )
     *             )
     *         }
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Indicates that the query failed.",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"ts.query"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={"Backend failure: foo"}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="string",
     *                     enum={}
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var ExpressionFactory $expressionFactory
     * @var GraphiteBackend $tsBackend
     *
     * @return JsonResponse
     */
    public function query(Request $request, SerializerInterface $serializer,
                          ExpressionFactory $expressionFactory,
                          GraphiteBackend $tsBackend)
    {
        $env = new Envelope('ts.query',
                            'body',
                            [
                                new RequestParameter('expression', RequestParameter::ARRAY, null, true),
                                new RequestParameter('from', RequestParameter::DATETIME, new QueryTime('-24h'), false),
                                new RequestParameter('until', RequestParameter::DATETIME, new QueryTime('now'), false),
                                new RequestParameter('aggregation_func', RequestParameter::STRING, 'avg', false),
                            ],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        // parse the given path expression
        try {
            $exp = $expressionFactory->createFromJson($env->getParam('expression'));
        } catch (ParsingException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }
        // ask the time series backend to perform the query
        try {
            $tss = $tsBackend->tsQuery(
                $exp,
                $env->getParam('from'),
                $env->getParam('until'),
                $env->getParam('aggregation_func')
            );
            $env->setData($tss);
        } catch (BackendException $ex) {
            $env->setError($ex->getMessage());
            // TODO: check HTTP error codes used
            return $this->json($env, 400);
        }
        return $this->json($env);
    }

    /**
     * List available time series keys matching the given path expression
     *
     * @Route("/list/", methods={"GET"}, name="list")
     * @SWG\Tag(name="Time Series")
     * @SWG\Parameter(
     *     name="path",
     *     in="query",
     *     type="string",
     *     description="Path expression string",
     *     required=false,
     *     default="*",
     * )
     * @SWG\Parameter(
     *     name="absolute_paths",
     *     in="query",
     *     type="boolean",
     *     description="Return absolute paths (rather than relative)",
     *     required=false,
     *     default=false
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Indicates the list query succeeded.",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"ts.list"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="array",
     *                     description="Array of time series paths that match the query",
     *                     items=@SWG\Schema(ref=@Model(type=\App\Expression\PathExpression::class, groups={"public", "list"}))
     *                 )
     *             )
     *         }
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Indicates that the given expression could not be parsed.",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"ts.list"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="string",
     *                     enum={}
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     *
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var GraphiteBackend $tsBackend
     *
     * @return JsonResponse
     */
    public function list(Request $request, SerializerInterface $serializer,
                         GraphiteBackend $tsBackend)
    {
        $env = new Envelope('ts.list',
                            'query',
                            [
                                new RequestParameter('path', RequestParameter::STRING, '*', false),
                                new RequestParameter('absolute_paths', RequestParameter::BOOL, false),
                            ],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        // parse the given path expression
        $pathExp = new PathExpression($env->getParam('path'));
        // ask the time series backend to find us a list of paths
        try {
            $paths = $tsBackend->pathListQuery($pathExp,
                                               $env->getParam('absolute_paths'));
            $env->setData($paths);
        } catch (BackendException $ex) {
            $env->setError($ex->getMessage());
            // TODO: check HTTP error codes used
            return $this->json($env, 400);
        }
        return $this->json($env, 200, [], ['groups' => ['public', 'list']]);
    }
}
