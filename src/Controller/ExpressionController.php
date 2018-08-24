<?php

namespace App\Controller;

use App\Expression\ExpressionFactory;
use App\Expression\Functions\Registry;
use App\Expression\ParsingException;
use App\Response\Envelope;
use App\Response\RequestParameter;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class ExpressionController
 * @package App\Controller
 * @Route("/expression", name="expression_")
 */
class ExpressionController extends ApiController
{
    /**
     * List expression functions
     *
     * Gets a list of supported transformation function prototypes. Useful for
     * presenting available functions in an expression builder UI.
     *
     * @Route("/functions/", methods={"GET"}, name="functions")
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
     *
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
     * Validate expression
     *
     * Validates the given JSON-formatted expression object for syntactic
     * correctness.
     *
     * @Route("/validate/", methods={"POST"}, name="validate")
     * @SWG\Tag(name="Expression")
     * @SWG\Parameter(
     *     name="query",
     *     in="body",
     *     type="object",
     *     description="Query object. Due to limitations in the current API documentation, the full expression schema cannot be properly described. See the various `*Expression` model definitions for more information about types of supported expressions.",
     *     required=true,
     *     @SWG\Schema(
     *         @SWG\Property(
     *             property="expression",
     *             type="object",
     *             description="JSON-encoded expression object.",
     *             ref=@Model(type=\App\Expression\AbstractExpression::class, groups={"public"})
     *        )
     *     ),
     *     @SWG\Schema(ref=@Model(type=\App\Expression\PathExpression::class, groups={"public"})),
     *     @SWG\Schema(ref=@Model(type=\App\Expression\ConstantExpression::class, groups={"public"})),
     *     @SWG\Schema(ref=@Model(type=\App\Expression\FunctionExpression::class, groups={"public"}))
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Indicates that the given expression was validated successfully. The validated expression is also returned in the response.",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"expression.validate"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="object",
     *                     @SWG\Schema(ref=@Model(type=\App\Expression\AbstractExpression::class, groups={"public"}))
     *                 )
     *             )
     *         }
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Indicates that the given expression could not be validated.",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"expression.validate"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={"Validation error: unknown function 'foo'"}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="string",
     *                     enum={}
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var ExpressionFactory $expressionFactory
     *
     * @return JsonResponse
     */
    public function validate(Request $request, SerializerInterface $serializer,
                             ExpressionFactory $expressionFactory)
    {
        $env = new Envelope('expression.validate',
                            'body',
                            [
                                new RequestParameter('expression', RequestParameter::ARRAY, null, true),
                            ],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        try {
            $exp = $expressionFactory->createFromJson($env->getParam('expression'));
            $env->setData($exp);
            return $this->json($env);
        } catch (ParsingException $exception) {
            $env->setError($exception->getMessage());
            return $this->json($env, 400);
        }
    }

    /**
     * Parse a "canonical" graphite-style expression string into a
     * JSON-formatted expression object
     *
     * @Route("/parse/", methods={"POST"}, name="parse")
     * @SWG\Tag(name="Expression")
     * @SWG\Parameter(
     *     name="query",
     *     in="body",
     *     type="object",
     *     description="Query object",
     *     required=true,
     *     @SWG\Schema(
     *         @SWG\Property(
     *                     property="expression_canonical",
     *                     type="string",
     *                     example="scaleToSeconds(a.test.series, 8)",
     *                     description="Canonical graphite-style expression string."
     *         )
     *     )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Indicates that the given expression was parsed successfully. The parsed expression is returned in the response.",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"expression.parse"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="object",
     *                     @SWG\Schema(ref=@Model(type=\App\Expression\AbstractExpression::class, groups={"public"}))
     *                 )
     *             )
     *         }
     *     )
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Indicates that the given expression could not be parsed.",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"expression.parse"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={"Parse error: unknown function 'foo'"}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="string",
     *                     enum={}
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var ExpressionFactory $expressionFactory
     *
     * @return JsonResponse
     */
    public function parse(Request $request, SerializerInterface $serializer,
                          ExpressionFactory $expressionFactory)
    {
        $env = new Envelope('expression.parse',
                            'body',
                            [
                                new RequestParameter('expression_canonical', RequestParameter::STRING, null, true),
                            ],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        try {
            $exp = $expressionFactory->createFromCanonical($env->getParam('expression_canonical'));
            $env->setData($exp);
            return $this->json($env);
        } catch (ParsingException $exception) {
            $env->setError($exception->getMessage());
            return $this->json($env, 400);
        }
    }
}
