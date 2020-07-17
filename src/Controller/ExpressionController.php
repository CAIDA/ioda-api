<?php
/**
 * This software is Copyright (c) 2013 The Regents of the University of
 * California. All Rights Reserved. Permission to copy, modify, and distribute this
 * software and its documentation for academic research and education purposes,
 * without fee, and without a written agreement is hereby granted, provided that
 * the above copyright notice, this paragraph and the following three paragraphs
 * appear in all copies. Permission to make use of this software for other than
 * academic research and education purposes may be obtained by contacting:
 *
 * Office of Innovation and Commercialization
 * 9500 Gilman Drive, Mail Code 0910
 * University of California
 * La Jolla, CA 92093-0910
 * (858) 534-5815
 * invent@ucsd.edu
 *
 * This software program and documentation are copyrighted by The Regents of the
 * University of California. The software program and documentation are supplied
 * "as is", without any accompanying services from The Regents. The Regents does
 * not warrant that the operation of the program will be uninterrupted or
 * error-free. The end-user understands that the program was developed for research
 * purposes and is advised not to rely exclusively on the program for any reason.
 *
 * IN NO EVENT SHALL THE UNIVERSITY OF CALIFORNIA BE LIABLE TO ANY PARTY FOR
 * DIRECT, INDIRECT, SPECIAL, INCIDENTAL, OR CONSEQUENTIAL DAMAGES, INCLUDING LOST
 * PROFITS, ARISING OUT OF THE USE OF THIS SOFTWARE AND ITS DOCUMENTATION, EVEN IF
 * THE UNIVERSITY OF CALIFORNIA HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE. THE UNIVERSITY OF CALIFORNIA SPECIFICALLY DISCLAIMS ANY WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE. THE SOFTWARE PROVIDED HEREUNDER IS ON AN "AS
 * IS" BASIS, AND THE UNIVERSITY OF CALIFORNIA HAS NO OBLIGATIONS TO PROVIDE
 * MAINTENANCE, SUPPORT, UPDATES, ENHANCEMENTS, OR MODIFICATIONS.
 */

namespace App\Controller;

use App\Expression\ExpressionFactory;
use App\Expression\Functions\Registry;
use App\Expression\ParsingException;
use App\Expression\PathExpression;
use App\Response\Envelope;
use App\Response\RequestParameter;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    public function functions(Request $request, SerializerInterface $serializer,
                              Registry $functionRegistry)
    {
        $env = new Envelope('expression.functions',
                            'query',
                            [],
                            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }
        $env->setData($functionRegistry);
        return $this->json($env);
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

    /**
     * Whitelist Path Expression
     *
     * Generate authorization whitelist regexes for the given JSON-formatted
     * path expression object.
     *
     * @Route("/whitelist/", methods={"POST"}, name="whitelist")
     * @SWG\Tag(name="Expression")
     * @SWG\Parameter(
     *     name="query",
     *     in="body",
     *     type="object",
     *     description="Path Expression object",
     *     required=true,
     *     @SWG\Schema(
     *         @SWG\Property(
     *             property="expression",
     *             type="object",
     *             description="JSON-encoded expression object.",
     *             ref=@Model(type=\App\Expression\PathExpression::class, groups={"public"})
     *        )
     *     )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="List of regexes that can be used to whitelist the given Path Expression",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"expression.whitelist"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="array",
     *                     items=@SWG\Schema(ref=@Model(type=\App\Expression\PathExpression::class, groups={"public"}))
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
     *                     enum={"expression.whitelist"}
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
    public function whitelist(Request $request, SerializerInterface $serializer,
                              ExpressionFactory $expressionFactory)
    {
        $env = new Envelope('expression.whitelist',
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
            $exp = PathExpression::createFromJson($expressionFactory,
                                                  $env->getParam('expression'));
            /** @var PathExpression $exp */
            $env->setData($exp->generateWhitelist());
            return $this->json($env);
        } catch (ParsingException $exception) {
            $env->setError($exception->getMessage());
            return $this->json($env, 400);
        }
    }
}
