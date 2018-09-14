<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class ApiController extends AbstractController
{
    protected function json($data, int $status = 200, array $headers = array(),
                            array $context = array()): JsonResponse
    {
        return parent::json($data, $status, $headers,
                            array_merge(['groups'=>['public']], $context));
    }
}
