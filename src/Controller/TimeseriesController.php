<?php

namespace App\Controller;

use App\Expression\ExpressionFactory;
use App\Expression\ParsingException;
use App\Expression\PathExpression;
use App\MetadataEntities\MetadataEntitiesService;
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
    public function query(Request $request, SerializerInterface $serializer,
                          ExpressionFactory $expressionFactory,
                          GraphiteBackend $tsBackend)
    {
        $env = new Envelope('ts.query',
                            'body',
                            [
                                new RequestParameter('expression', RequestParameter::ARRAY, null, false),
                                new RequestParameter('expressions', RequestParameter::ARRAY, null, false),
                                new RequestParameter('from', RequestParameter::DATETIME, new QueryTime('-24h'), false),
                                new RequestParameter('until', RequestParameter::DATETIME, new QueryTime('now'), false),
                                new RequestParameter('aggregation_func', RequestParameter::STRING, 'avg', false),
                                new RequestParameter('annotate', RequestParameter::BOOL, false, false),
                                new RequestParameter('adaptive_downsampling', RequestParameter::BOOL, true, false),
                            ],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        // TODO: adaptive_downsampling should be protected by authorization role?

        // we need either expression or expressions
        $oneExp = $env->getParam('expression');
        $manyExps = $env->getParam('expressions');

        if ($oneExp && $manyExps) {
            $env->setError("Only one of 'expression' or 'expressions' parameters can be set");
            return $this->json($env, 400);
        }

        if ($oneExp) {
            $rawExps = [$oneExp];
        } elseif ($manyExps) {
            $rawExps = $manyExps;
        } else {
            $env->setError("Either 'expression' or 'expressions' parameters must be set");
            return $this->json($env, 400);
        }
        $exps = [];
        try {
            foreach ($rawExps as $exp) {
                $exps[] = $expressionFactory->createFromJson($exp);
            }
        } catch (ParsingException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }
        // ask the time series backend to perform the query
        try {
            $tss = $tsBackend->tsQuery(
                $exps,
                $env->getParam('from'),
                $env->getParam('until'),
                $env->getParam('aggregation_func'),
                $env->getParam('annotate'),
                $env->getParam('adaptive_downsampling')
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
     * @var Humanizer $humanizer
     *
     * @return JsonResponse
     */
    public function list(Request $request, SerializerInterface $serializer,
                         GraphiteBackend $tsBackend, Humanizer $humanizer)
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
        $pathExp = new PathExpression($humanizer, $env->getParam('path'));
        // ask the time series backend to find us a list of paths
        try {
            $paths = $tsBackend->pathListQuery($pathExp,
                                               $env->getParam('absolute_paths'));
            $env->setData($paths);
        } catch (BackendException $ex) {
            $env->setError($ex->getMessage());
            // TODO: check HTTP error codes used
            return $this->json($env, 400);
        } catch (ParsingException $ex) {
            $env->setError($ex->getMessage());
            // TODO: check HTTP error codes used
            return $this->json($env, 400);
        }
        return $this->json($env, 200, [], ['groups' => ['public', 'list']]);
    }

    /**
     * @Route("/{entityType}/{entityCode}", methods={"GET"}, name="get")
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
                new RequestParameter('from', RequestParameter::INTEGER, null, true),
                new RequestParameter('until', RequestParameter::INTEGER, null, true),
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

        $env->setData("lala");
        return $this->json($env);
    }
}
