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
     * Get datasources
     *
     * @Route("/", methods={"GET"}, name="getall")
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
     * @var string|null datasource
     * @var Request $request
     * @var SerializerInterface $serializer
     * @return JsonResponse
     */
    public function datasourceLookup(?string $datasource, Request $request, DatasourceService $datasourceService)
    {
        $env = new Envelope('datasources',
            'query',
            [],
            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        // $datasource is null, return all datasources
        if(!isset($datasource)){
            $env->setData(array_values($datasourceService->getAllDatasources()));
            return $this->json($env);
        }

        // $datasource is not null, return only the matching datasource
        try {
            $env->setData($datasourceService->getDatasource($datasource));
        } catch (\InvalidArgumentException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }
        return $this->json($env);
    }
}
