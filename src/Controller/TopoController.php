<?php

namespace App\Controller;

use App\Response\Envelope;
use App\Topo\TopoService;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class TopoController
 * @package App\Controller
 * @Route("/topo", name="topo_")
 */
class TopoController extends ApiController
{
    /**
     * List available topographic databases
     *
     * @Route("/databases/", methods={"GET"}, name="databases")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns a list of available topographic databases",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"topo.databases"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="array",
     *                     items=@SWG\Property(type="string")
     *                 )
     *             )
     *         }
     *     )
     * )
     * @var Request $request
     * @var SerializerInterface
     * @var TopoService $topoService
     * @return JsonResponse
     */
    public function databases(Request $request, SerializerInterface $serializer,
                              TopoService $topoService)
    {
        $env = new Envelope('topo.databases',
                            'query',
                            [],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }
        $env->setData($topoService->getDatabases());
        return $this->json($env);
    }

    /**
     * Get topographic database information
     *
     * @Route("/databases/{db}/",
     *     methods={"GET"},
     *     name="database")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns information about the given topographic database"
     * )
     */
    /*
    public function database($db)
    {
        return $this->json([
            "database info for $db",
        ]);
    }
    */

    /**
     * List available tables for the given topographic database
     *
     * @Route("/databases/{db}/tables/",
     *     methods={"GET"},
     *     name="database_tables")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns a list of the available tables for the given topographic database",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"topo.tables"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="array",
     *                     items=@SWG\Property(type="string")
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @var string $db
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var TopoService $topoService
     * @return JsonResponse
     */
    public function tables(string $db, Request $request,
                           SerializerInterface $serializer,
                           TopoService $topoService)
    {
        $env = new Envelope('topo.tables',
                            'query',
                            [],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }
        try {
            $env->setData($topoService->getTables($db));
        } catch (\InvalidArgumentException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }
        return $this->json($env);
    }

    /**
     * Get TopoJSON for the given database table
     *
     * @Route("/databases/{db}/tables/{table}/",
     *     methods={"GET"},
     *     name="database_table")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns the TopoJSON data for the given database table",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"topo.topojson"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data"
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @var string $db
     * @var string $table
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var TopoService $topoService
     * @return JsonResponse
     */
    public function table(string $db, string $table, Request $request,
                          SerializerInterface $serializer,
                          TopoService $topoService)
    {
        $env = new Envelope('topo.topojson',
                            'query',
                            [],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }
        try {
            $env->setData($topoService->getTopoJson($db, $table));
        } catch (\InvalidArgumentException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }
        return $this->json($env);
    }
}
