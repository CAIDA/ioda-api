<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class SymUrlController
 * @package App\Controller
 */
class SymUrlController extends Controller
{
    /**
     * @Route("/@{short}", methods={"GET"}, name="sym_go")
     */
    public function go($short)
    {
        return $this->json([
            "going to short url: $short"
        ]);
    }

    /**
     * @Route("/sym/{short}", methods={"GET"}, name="sym_get")
     */
    public function get($short)
    {
        return $this->json([
                               "get info for $short",
                           ]);
    }

    /**
     * @Route("/sym", methods={"POST"}, name="sym_new")
     */
    public function new()
    {
        return $this->json([
            "create new symurl"
        ]);
    }
}
