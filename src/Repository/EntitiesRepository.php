<?php

namespace App\Repository;

use App\Entity\Outages\MetadataEntity;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

;
class EntitiesRepository extends ServiceEntityRepository
{
    const METADATA_DATA_CACHE_TIMEOUT = 3600;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MetadataEntity::class);
    }

    /**
     * Return name of a unique entity
     */
    public function findName($type, $code)
    {
        $qb = $this->createQueryBuilder('m')
            ->innerJoin('m.type', 't')
            ->setMaxResults(1);

        $qb->andWhere('t.type = :type')->setParameter('type', $type);
        $qb->andWhere('m.code = :code')->setParameter('code', $code);

        $q = $qb->getQuery();

        $q->useResultCache(true, self::METADATA_DATA_CACHE_TIMEOUT, 'metadata.entity.name');

        $entities = $q->getResult();
        return empty($entities) ? null : $entities[0];
        // return empty($entities) ? null : $entities[0]->getName();
    }

    /**
     * Return a sets of entities
     */
    public function findMetadata($type, $code=null, $name=null, $limit=null, $wildcard=false)
    {
        $qb = $this->createQueryBuilder('m')
            ->innerJoin('m.type', 't');

        $qb->andWhere('t.type = :type')->setParameter('type', $type);
        if (!empty($code)) {
            if ($wildcard) {
                $code = '%'.$code.'%';
            }
            $qb->andWhere('LOWER(m.code) LIKE LOWER(:code)')->setParameter('code', $code);
        }
        if (!empty($name)) {
            if ($wildcard) {
                $name = '%'.$name.'%';
            }
            $qb->andWhere('LOWER(m.name) LIKE LOWER(:name)')->setParameter('name', $name);
        }
        if ($limit) {
            $qb->setMaxResults($limit);
        }

        $res = $qb->getQuery()->getResult();

        // force results to never look up related entities
        // this effectively disables the getRelationships method
        /** @var $prop \ReflectionProperty */
        $prop = $this->getClassMetadata()->reflFields["relationships"];
        foreach ($res as &$entity) {
            $prop->getValue($entity)->setInitialized(true);
        }

        return $res;
    }

    /**
     * Return all relationships between two sets of entities along with the entities that have the relationships
     */
    public function findRelationships($type=null, $code=null, $relatedType=null, $relatedCode=null, $limit=null)
    {
        $em = $this->getEntityManager();

        $rsm = new ResultSetMappingBuilder($em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);
        $rsm->addRootEntityFromClassMetadata('App\Entity\Outages\MetadataEntity', 'm');
        $rsm->addJoinedEntityFromClassMetadata('App\Entity\Outages\MetadataEntity', 'om', 'm', 'relationships');

        $parameters = array_filter(
            [
                (!empty($relatedType) ? 'mt.type = :relatedType' : null),
                (!empty($relatedCode) ? 'm.code = :relatedCode' : null),
                (!empty($type) ? 'omt.type = :type' : null),
                (!empty($code) ? 'om.code = :code' : null),
            ]
        );
        $sql = 'SELECT ' . $rsm->generateSelectClause() . '
            FROM
                mddb_entity m
                INNER JOIN mddb_entity_type mt ON m.type_id = mt.id
                INNER JOIN mddb_entity_relationship r ON m.id = r.from_id
                INNER JOIN mddb_entity om ON om.id = r.to_id
                INNER JOIN mddb_entity_type omt ON om.type_id = omt.id'
            . (!empty($parameters) ? ' WHERE ' . implode(' AND ', $parameters) : '')
            . (($limit) ? ' LIMIT ' . $limit: '');

        $q = $em->createNativeQuery($sql, $rsm)
            ->setParameters([
                'type' => $type,
                'code' => $code,
                'relatedType' => $relatedType,
                'relatedCode' => $relatedCode,
            ]);

        $res = $q->getResult();

        // force related entities to never resolve *their* relations
        /** @var $prop \ReflectionProperty */
        $prop = $this->getClassMetadata()->reflFields["relationships"];
        foreach ($res as &$o) {
            foreach ($o->getRelationships() as &$om)
                $prop->getValue($om)->setInitialized(true);
        }

        return $res;
    }
}
