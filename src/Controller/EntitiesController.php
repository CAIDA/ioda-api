<?php

namespace App\Controller;

use App\MetadataEntities\MetadataEntitiesService;
use App\Response\Envelope;
use App\Response\RequestParameter;
use App\SymUrl\SymUrlService;
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
     * Expand an existing short URL
     *
     * Returns a JSON object with metadata about the given short URL. Note that
     * this also updates last-used times and counters unless the "no_stats"
     * parameter is provided.
     *
     * @Route("/{entityType}/{entityCode}", methods={"GET"}, name="get", defaults={"entityCode"=null, "relatedTo"=null})
     *
     * @var string $entityType
     * @var string|null $entityCode
     * @var string|null $relatedTo
     * @var Request $request
     * @var SerializerInterface $serializer
     * @var MetadataEntitiesService
     * @return JsonResponse
     */
    public function lookup(
        string $entityType, ?string $entityCode, ?string $relatedTo,
        Request $request,
        SerializerInterface $serializer,
        MetadataEntitiesService $service
    ){
        $env = new Envelope('entities.tests',
            'query',
            [
                new RequestParameter('relatedTo', RequestParameter::STRING, null, false),
            ],
            $request
        );
        if ($env->getError()) {
            return $this->json($env, 400);
        }

        /* LOCAL PARAM PARSING */
        $relatedTo = $env->getParam('relatedTo');
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

        $entity = $service->lookup($entityType, $entityCode, $relatedTo[0], $relatedTo[1]);
        $env->setData($entity);
        return $this->json($env);
    }
}
