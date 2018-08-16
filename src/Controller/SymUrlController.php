<?php

namespace App\Controller;

use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class SymUrlController
 * @package App\Controller
 */
class SymUrlController extends ApiController
{
    /**
     * @Route("/@{short}/", methods={"GET"}, name="sym_go")
     * @SWG\Tag(name="URL Shortener")
     * @SWG\Response(
     *     response=301,
     *     description="Returns a redirect to the long URL referenced by the given short URL tag"
     * )
     */
    public function go($short)
    {
        return $this->json([
            "going to short url: $short"
        ]);
    }

    /**
     * @Route("/sym/{short}/", methods={"GET"}, name="sym_get")
     * @SWG\Tag(name="URL Shortener")
     * @SWG\Response(
     *     response=200,
     *     description="Returns information for the given short URL tag"
     * )
     */
    public function get($short)
    {
        return $this->json([
                               "get info for $short",
                           ]);
    }

    /**
     * @Route("/sym/", methods={"POST"}, name="sym_new")
     * @SWG\Tag(name="URL Shortener")
     * @SWG\Response(
     *     response=200,
     *     description="Returns information about the newly created short URL"
     * )
     */
    public function new()
    {
        return $this->json([
            "create new symurl"
        ]);
    }
}
