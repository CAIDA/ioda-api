<?php

namespace App\Controller;

use App\Expression\PathExpression;
use App\Response\Envelope;
use App\Response\RequestParameter;
use App\TimeSeries\Backend\BackendException;
use App\TimeSeries\Backend\GraphiteBackend;
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
     * @Route("/query/", methods={"POST"}, name="query")
     * @SWG\Tag(name="Time Series")
     * @SWG\Response(
     *     response=200,
     *     description="Returns the result of executing the given query"
     * )
     */
    public function query()
    {
        return $this->json([
            'type' => 'query',
        ]);
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
     *                     items=@SWG\Schema(ref=@Model(type=\App\TimeSeries\TimeSeriesSet::class, groups={"metaonly"}))
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
