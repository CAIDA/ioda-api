<?php


namespace App\MetadataEntities;


use App\Entity\Alerts\MetadataEntity;
use App\Repository\EntitiesRepository;

class MetadataEntitiesService
{
    /**
     * @var EntitiesRepository
     */
    private $repo;

    public function __construct(EntitiesRepository $entitiesRepo)
    {
        $this->repo = $entitiesRepo;
    }

    /**
     * @param $type
     * @param null $code
     * @param null $relatedToType
     * @param null $relatedToCode
     * @return MetadataEntity[]
     */
    public
    function lookup($type, $code=null, $relatedToType=null, $relatedToCode=null)
    {
        if ($relatedToType) {
            /* @var $metas MetadataEntity[] */
            $metas =
                $this->repo->findRelationships($type, $code,
                    $relatedToType, $relatedToCode);
            if ($relatedToCode) {
                // if an exact relation was given, there can be at most
                // one 'related' entity
                assert(count($metas) <= 1);
                if (count($metas)) {
                    $metas = $metas[0]->getRelationships();
                }
            }
        } else {
            $metas = $this->repo->findMetadata($type, $code);
        }

        return $metas;
    }

    /**
     * @var string $type
     * @var string $code
     * @return MetadataEntity|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getOne(string $type, string $code): ?MetadataEntity
    {
        return $this->repo->findName($type, $code);
    }
}
