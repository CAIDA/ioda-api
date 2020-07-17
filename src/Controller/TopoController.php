<?php

namespace App\Controller;

use App\Response\Envelope;
use App\Service\TopoService;
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
     * Get topographic database information
     *
     * @Route("/{entityType}", methods={"GET"}, name="get")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns information about the given topographic database",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"topo.get"}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="object",
     *                     description="The corresponding topographic data",
     *                     @SWG\Property(
     *                         property="entityType",
     *                         type="string"
     *                     ),
     *                     @SWG\Property(
     *                         property="idField",
     *                         type="string"
     *                     ),
     *                     @SWG\Property(
     *                         property="topology",
     *                         type="object"
     *                     ),
     *                 )
     *             )
     *         }
     *     )
     * )
     * @var string $entityType
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var TopoService $topoService
     * @return JsonResponse
     */
    public function topoLookup(string $entityType, Request $request,
                          SerializerInterface $serializer,
                          TopoService $topoService)
    {
        $env = new Envelope('topo.get',
            'query',
            [],
            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }
        try {
            $env->setData([
                "entityType" => $entityType,
                "idField" => $topoService->getIdField($entityType),
                "topology" => $topoService->getTopoJson($entityType)
            ]);
        } catch (\InvalidArgumentException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }
        return $this->json($env);
    }
}
