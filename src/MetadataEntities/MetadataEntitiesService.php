<?php


namespace App\MetadataEntities;


use App\Entity\Outages\MetadataEntity;
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
    function lookup($type, $code=null, $relatedToType=null, $relatedToCode=null, $limit=null)
    {
        if ($relatedToType) {
            /* @var $metas MetadataEntity[] */
            $metas =
                $this->repo->findRelationships($type, $code,
                    $relatedToType, $relatedToCode, $limit);
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
     * TODO: deprecate this function, and merge with `lookup`
     * @param $type
     * @param null $code
     * @param null $name
     * @param integer $limit
     * @param bool $wildcard
     * @return MetadataEntity[]
     */
    public function search($type, $code = null, $name = null, $limit=null, $wildcard=false)
    {
        return $this->repo->findMetadata($type, $code, $name, $limit, $wildcard);
    }
}
