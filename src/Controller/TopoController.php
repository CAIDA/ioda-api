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
     * @Route("/{entityType}",
     *     methods={"GET"},
     *     name="get")
     * @SWG\Tag(name="Topographic")
     * @SWG\Response(
     *     response=200,
     *     description="Returns information about the given topographic database"
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
        $env = new Envelope('topo.topojson',
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
