<?php

namespace App\Controller;

use App\Response\Envelope;
use App\Service\DatasourceService;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class DatasourcesController
 * @package App\Controller
 * @Route("/datasources", name="datasources_")
 */
class DatasourcesController extends ApiController
{
    /**
     * Get all datasources
     *
     * @Route("/", methods={"GET"}, name="listall")
     * @SWG\Tag(name="Data Sources")
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
     *                     enum={"datasources.all"}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="array",
     *                     @SWG\Items(
     *                          ref=@Model(type=\App\Entity\Ioda\DatasourceEntity::class, groups={"public"})
     *                     )
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @var Request $request
     * @var SerializerInterface $serializer
     * @return JsonResponse
     */
    public function datasources(Request $request, DatasourceService $service)
    {
        $env = new Envelope('datasources.all',
            'query',
            [],
            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }
        $env->setData(array_values($service->getDatasources()));
        return $this->json($env);
    }

    /**
     * Get all datasources
     *
     * @Route("/{datasource}", methods={"GET"}, name="findone")
     * @SWG\Tag(name="Data Sources")
     * @SWG\Response(
     *     response=200,
     *     description="Return data source matched by the lookup term",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"datasources.lookup"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     ref=@Model(type=\App\Entity\Ioda\DatasourceEntity::class, groups={"public"})
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @var datasource
     * @var Request $request
     * @var SerializerInterface $serializer
     * @return JsonResponse
     */
    public function datasourceLookup(string $datasource, Request $request, DatasourceService $datasourceService)
    {
        $env = new Envelope('datasources.lookup',
            'query',
            [],
            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        $datasources = $datasourceService->getDatasources();
        try {
            if (!array_key_exists($datasource, $datasources)) {
                throw new \InvalidArgumentException("Unknown datasource '$datasource'");
            }
            $env->setData($datasources[$datasource]);
        } catch (\InvalidArgumentException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }
        return $this->json($env);
    }
}
