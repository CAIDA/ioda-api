<?php

namespace App\Controller;

use App\Expression\Functions\Registry;
use App\Response\Envelope;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\SerializerInterface;

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
     *     description="Returns a list of supported transformation functions",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"expression.functions"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     ref=@Model(type=\App\Expression\Functions\Registry::class, groups={"public"})
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var Registry $functionRegistry
     * @return JsonResponse
     */
    public function functions(Request $request, SerializerInterface $serializer, Registry $functionRegistry)
    {
        $envelope = new Envelope(
            'expression.functions',
            null,
            $request->server->get('REQUEST_TIME')
        );
        $envelope->setData($functionRegistry);
        return $this->json($envelope);
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
