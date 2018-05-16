<?php

namespace App\Controller;

use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class TimeseriesController
 * @package App\Controller\Timeseries
 * @Route("/ts")
 */
class TimeseriesController extends AbstractController
{
    /**
     * @Route("/query/", methods={"POST"}, name="ts_query")
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
     * @Route("/list/", methods={"GET"}, name="ts_list")
     * @SWG\Tag(name="Time Series")
     * @SWG\Response(
     *     response=200,
     *     description="Returns a list of time series keys matching the given query"
     * )
     */
    public function list()
    {
        return $this->json([
            'type' => 'list',
        ]);
    }
}
