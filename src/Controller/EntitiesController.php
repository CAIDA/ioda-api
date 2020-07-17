<?php

namespace App\Controller;

use App\Service\MetadataEntitiesService;
use App\Response\Envelope;
use App\Response\RequestParameter;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class EntitiesController
 * @package App\Controller
 * @Route("/entities", name="entities_")
 */
class EntitiesController extends ApiController
{
    /**
     * Lookup metadata entities
     *
     * Returns a JSON object with metadata for the searched entities.
     *
     * @Route("/{entityType}/{entityCode}", methods={"GET"}, name="get", defaults={"entityType"=null,"entityCode"=null})
     * @SWG\Tag(name="Metadata Entities")
     * @SWG\Parameter(
     *     name="entityType",
     *     in="path",
     *     type="string",
     *     description="Type of the entity, e.g. country, region, asn",
     *     default=null
     * )
     * @SWG\Parameter(
     *     name="entityCode",
     *     in="path",
     *     type="string",
     *     description="Code of the entity, e.g. for United States the code is 'US'",
     *     required=false,
     *     default=null
     * )
     * @SWG\Parameter(
     *     name="relatedTo",
     *     in="query",
     *     type="string",
     *     description="Find entities related to another entity. Format: 'entityType[/entityCode]",
     *     required=false,
     * )
     * @SWG\Parameter(
     *     name="search",
     *     in="query",
     *     type="string",
     *     description="Search entities with name that matches the search term",
     *     required=false,
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Return an array of metadata entities",
     *     @SWG\Schema(
     *         allOf={
     *             @SWG\Schema(ref=@Model(type=Envelope::class, groups={"public"})),
     *             @SWG\Schema(
     *                 @SWG\Property(
     *                     property="type",
     *                     type="string",
     *                     enum={"sym.get"}
     *                 ),
     *                 @SWG\Property(
     *                     property="error",
     *                     type="string",
     *                     enum={}
     *                 ),
     *                 @SWG\Property(
     *                     property="data",
     *                     type="array",
     *                     @SWG\Items(
     *                          ref=@Model(type=\App\Entity\Ioda\MetadataEntity::class, groups={"public"})
     *                     )
     *                 )
     *             )
     *         }
     *     )
     * )
     *
     * @var string|null $entityType
     * @var string|null $entityCode
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var MetadataEntitiesService
     * @return JsonResponse
     */
    public function lookup(
        ?string $entityType, ?string $entityCode,
        Request $request,
        SerializerInterface $serializer,
        MetadataEntitiesService $service
    ){
        $env = new Envelope('entities.lookup',
            'query',
            [
                new RequestParameter('relatedTo', RequestParameter::STRING, null, false),
                new RequestParameter('search', RequestParameter::STRING, null, false),
                new RequestParameter('limit', RequestParameter::INTEGER, null, false),
            ],
            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        /* LOCAL PARAM PARSING */
        $search = $env->getParam('search');
        $relatedTo = $env->getParam('relatedTo');
        $limit = $env->getParam('limit');
        if($search){
            $entity = $service->search($entityType, null, $search, $limit, true);
            $env->setData($entity);
            return $this->json($env);
        }

        try {
            if ($relatedTo) {
                $relatedTo = explode('/', $relatedTo);
                if (count($relatedTo) > 2) {
                    throw new \InvalidArgumentException(
                        "relatedTo parameter must be in the form 'type[/code]'"
                    );
                }
                if (count($relatedTo) == 1) {
                    $relatedTo[] = null;
                }
            } else {
                $relatedTo = [null, null];
            }
        } catch (\InvalidArgumentException $ex) {
            $env->setError($ex->getMessage());
            return $this->json($env, 400);
        }

        $entity = $service->lookup($entityType, $entityCode, $relatedTo[0], $relatedTo[1], $limit);
        $env->setData($entity);
        return $this->json($env);
    }
}
