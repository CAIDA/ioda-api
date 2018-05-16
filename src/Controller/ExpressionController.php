<?php

namespace App\Controller;

use Swagger\Annotations as SWG;
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
     * @Route("/functions/", methods={"GET"}, name="expression_functions")
     * @SWG\Tag(name="Expression")
     * @SWG\Response(
     *     response=200,
     *     description="Returns a list of supported transformation functions"
     * )
     */
    public function functions()
    {
        return $this->json([
            'functions'
        ]);
    }

    /**
     * @Route("/validate/", methods={"POST"}, name="expression_validate")
     * @SWG\Tag(name="Expression")
     * @SWG\Response(
     *     response=200,
     *     description="Indicates that the given expression was validated successfully"
     * )
     */
    public function validate()
    {
        return $this->json([
            'validate'
        ]);
    }
}
