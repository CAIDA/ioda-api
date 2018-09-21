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
    public function database($db)
    {
        return $this->json([
            "database info for $db",
        ]);
    }

    /**
     * List available tables for the given topographic database
     *
     * @Route("/databases/{db}/tables/",
     *     methods={"GET"},
     *     name="database_tables")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns a list of the available tables for the given topographic database"
     * )
     */
    public function tables($db)
    {
        return $this->json([
            "topo table list for $db",
        ]);
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
     *     description="Returns the TopoJSON data for the given database table"
     * )
     */
    public function table($db, $table)
    {
        return $this->json([
            "topo database table $db/$table",
        ]);
    }
}
