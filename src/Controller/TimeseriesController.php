<?php

namespace App\Controller;

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
     * @Route("/query", methods={"POST"}, name="ts_query")
     */
    public function query()
    {
        return $this->json([
            'type' => 'query',
        ]);
    }

    /**
     * @Route("/list", methods={"GET"}, name="ts_list")
     */
    public function list()
    {
        return $this->json([
            'type' => 'list',
        ]);
    }
}
