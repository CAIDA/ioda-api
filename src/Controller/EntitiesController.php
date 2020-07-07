<?php

namespace App\Controller;

use App\MetadataEntities\MetadataEntitiesService;
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
 * Class SymUrlController
 * @package App\Controller
 * @Route("/entities", name="entities_")
 */
class EntitiesController extends ApiController
{
    /**
     * @Route("/{entityType}/{entityCode}", methods={"GET"}, name="get", defaults={"entityType"=null,"entityCode"=null})
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

        $entity = $service->lookup($entityType, $entityCode, $relatedTo[0], $relatedTo[1], $limit);
        $env->setData($entity);
        return $this->json($env);
    }
}
