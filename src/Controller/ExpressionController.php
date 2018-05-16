<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class ExpressionController
 * @package App\Controller
 * @Route("/expression")
 */
class ExpressionController extends Controller
{
    /**
     * @Route("/functions", methods={"GET"}, name="expression_functions")
     */
    public function functions()
    {
        return $this->json([
            'functions'
        ]);
    }

    /**
     * @Route("/validate", methods={"POST"}, name="expression_validate")
     */
    public function validate()
    {
        return $this->json([
            'validate'
        ]);
    }
}
