<?php

namespace App\Controller;

use App\Response\Envelope;
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
    const DATASOURCES = [
        "ucsd-nt" => [
            "datasource" => "ucsd-nt",
            "name" => "UCSD Network Telescope",
            "units" => "Unique Source IPs"
        ],
        "bgp" => [
            "datasource" => "bgp",
            "name" => "BGP",
            "units" => "Visible /24s"
        ],
        "ping-slash24" => [
            "datasource" => "ping-slash24",
            "name" => "Active Probing",
            "units" => "Up /24s"
        ],
    ];

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
    public function datasources(Request $request)
    {
        $env = new Envelope('datasources',
            'query',
            [],
            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }
        $env->setData(array_values(self::DATASOURCES));
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
    public function datasourceLookup(string $datasource, Request $request)
    {
        $env = new Envelope('datasources',
            'query',
            [],
            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        try {
            if (!array_key_exists($datasource, self::DATASOURCES)) {
                throw new \InvalidArgumentException("Unknown datasource '$datasource'");
            }
            $env->setData(self::DATASOURCES[$datasource]);
        } catch (\InvalidArgumentException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }
        return $this->json($env);
    }
}
