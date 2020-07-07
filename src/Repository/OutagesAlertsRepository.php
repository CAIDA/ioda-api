<?php

namespace App\Repository;

use App\Entity\Outages\OutagesAlert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;


class OutagesAlertsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, OutagesAlert::class);
    }

    /**
     * @param integer $from
     * @param integer $until
     * @param string|null $entityType
     * @param string|null $entityCode
     * @param string|null $datasource
     * @param integer|null $limit
     * @param integer|null $page
     * @return mixed
     */
    public function findAlerts($from, $until,
                               ?string $entityType, ?string $entityCode, ?string $datasource,
                               ?int $limit, ?int $page)
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.time', 'ASC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($from) {
            $qb->andWhere('a.time > :from')->setParameter('from', $from);
        }
        if ($until) {
            $qb->andWhere('a.time < :until')->setParameter('until', $until);
        }
        if (isset($entityType)) {
            $qb->andWhere('a.metaType = :metaType')
                ->setParameter('metaType', $entityType);
        }
        if (isset($entityCode)) {
            $qb->andWhere('a.metaCode = :metaCode')
                ->setParameter('metaCode', $entityCode);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * simple find, no related MDDB entities
     * @param $metaType
     * @param null $from
     * @param null $until
     * @param null $metaCode
     * @return mixed
     */
    public function findAlertsByMetadata(
        $metaType,
        $from = null,
        $until = null,
        $metaCode = null
    ){
        $em = $this->getEntityManager();

        $rsm = new ResultSetMappingBuilder($em,
            ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);
        $rsm->addRootEntityFromClassMetadata('CAIDA\Charthouse\MetadataBundle\Entity\MetadataEntity',
            'm');
        $rsm->addJoinedEntityFromClassMetadata('CAIDA\Charthouse\MetadataBundle\Entity\MetadataEntityType',
            'mt', 'm', 'type');
        $rsm->addJoinedEntityFromClassMetadata('CAIDA\Charthouse\MetadataBundle\Entity\MetadataEntityAttribute',
            'ma', 'm', 'attributes');
        // this is a horrifying hack. stash the alerts in the 'relationships' field of the mddb entity :o
        $rsm->addJoinedEntityFromClassMetadata('CAIDA\Charthouse\WatchtowerBundle\Entity\WatchtowerAlert',
            'a', 'm', 'relationships');

        $parameters = array_filter(
            [
                (!empty($from) ? 'a.time >= :from' : null),
                (!empty($until) ? 'a.time < :until' : null),
                (!empty($metaType) ? 'mt.type = :metaType' : null),
                (!empty($metaCode) ? 'm.code = :metaCode' : null),
            ]
        );

        $sql = 'SELECT ' . $rsm->generateSelectClause() . '
            FROM
                mddb_entity m,
                mddb_entity_type mt,
                mddb_entity_attribute ma,
                watchtower_alert a
                WHERE
                m.type_id = mt.id AND
                ma.metadata_id = m.id AND
                a.meta_type = mt.type AND a.meta_code = m.code'
            . (!empty($parameters) ?
                ' AND ' . implode(' AND ', $parameters) : '');

        $q = $em->createNativeQuery($sql, $rsm)
            ->setParameters([
                'from'     => $from,
                'until'    => $until,
                'metaType' => $metaType,
                'metaCode' => $metaCode,
            ]);

        return $q->getResult();
    }


    /**
     * complex find using related MDDB entities
     * @param $metaType
     * @param $relatedType
     * @param null $from
     * @param null $until
     * @param null $metaCode
     * @param null $relatedCode
     * @return mixed
     */
    public function findAlertsByRelatedMetadata(
        $metaType,
        $relatedType,
        $from = null,
        $until = null,
        $metaCode = null,
        $relatedCode = null
    ){
        $em = $this->getEntityManager();

        $rsm = new ResultSetMappingBuilder($em,
            ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);
        $rsm->addRootEntityFromClassMetadata('CAIDA\Charthouse\MetadataBundle\Entity\MetadataEntity',
            'rm');
        $rsm->addJoinedEntityFromClassMetadata('CAIDA\Charthouse\MetadataBundle\Entity\MetadataEntityType',
            'rmt', 'rm', 'type');
        $rsm->addJoinedEntityFromClassMetadata('CAIDA\Charthouse\MetadataBundle\Entity\MetadataEntity',
            'm', 'rm', 'relationships');
        $rsm->addJoinedEntityFromClassMetadata('CAIDA\Charthouse\MetadataBundle\Entity\MetadataEntityType',
            'mt', 'm', 'type');
        // this is a horrifying hack. stash the alerts in the 'relationships' field of the mddb entity :o
        $rsm->addJoinedEntityFromClassMetadata('CAIDA\Charthouse\WatchtowerBundle\Entity\WatchtowerAlert',
            'a', 'm', 'relationships');

        $parameters = array_filter(
            [
                (!empty($from) ? 'a.time >= :from' : null),
                (!empty($until) ? 'a.time < :until' : null),
                (!empty($relatedCode) ? 'rm.code = :relatedCode' : null),
                (!empty($metaCode) ? 'm.code = :metaCode' : null),
            ]
        );

        $sql = 'SELECT ' . $rsm->generateSelectClause() . '
            FROM
                mddb_entity m,
                mddb_entity_type mt,
                mddb_entity rm,
                mddb_entity_type rmt,
                mddb_entity_relationship r,
                watchtower_alert a
                WHERE
                mt.type = :metaType AND
                m.type_id = mt.id AND
                rmt.type = :relatedType AND
                rm.type_id = rmt.id AND
                r.from_id = m.id AND r.to_id = rm.id AND
                a.meta_type = mt.type AND a.meta_code = m.code'
            . (!empty($parameters) ?
                ' AND ' . implode(' AND ', $parameters) : '');

        $q = $em->createNativeQuery($sql, $rsm)
            ->setParameters([
                'from'     => $from,
                'until'    => $until,
                'metaType' => $metaType,
                'metaCode' => $metaCode,
                'relatedType' => $relatedType,
                'relatedCode' => $relatedCode,
            ]);

        return $q->getResult();
    }
}
