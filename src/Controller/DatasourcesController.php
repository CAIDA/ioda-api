<?php

namespace App\Controller;

use App\Response\Envelope;
use App\Service\DatasourceService;
use Nelmio\ApiDocBundle\Annotation\Model;
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
     * @Route("/",
     *     methods={"GET"},
     *     name="listall")
     *
     * @var Request $request
     * @var SerializerInterface $serializer
     * @return JsonResponse
     */
    public function datasources(Request $request, DatasourceService $service)
    {
        $env = new Envelope('datasources',
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
     * @Route("/{datasource}",
     *     methods={"GET"},
     *     name="findone")
     *
     * @var datasource
     * @var Request $request
     * @var SerializerInterface $serializer
     * @return JsonResponse
     */
    public function datasourceLookup(string $datasource, Request $request, DatasourceService $datasourceService)
    {
        $env = new Envelope('datasources',
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
