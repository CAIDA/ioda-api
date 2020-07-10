<?php

namespace App\Repository;

use App\Entity\Outages\OutagesAlert;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
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
     * @return mixed
     */
    public function findAlerts($from, $until,
                               ?string $entityType, ?string $entityCode, ?string $datasource)
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.time', 'ASC');

        if ($from) {
            $qb->andWhere('a.time > :from')->setParameter('from', $from);
        }
        if ($until) {
            $qb->andWhere('a.time < :until')->setParameter('until', $until);
        }
        if ($datasource){
            $qb->andWhere('a.fqid LIKE :datasource')->setParameter('datasource', "%".$datasource."%");
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
     * Return alerts, sorted by time
     * @param integer $from
     * @param integer $until
     * @param string|null $entityType
     * @param string|null $entityCode
     * @param string|null $datasource
     * @return mixed
     */
    public function findAlertsNativeSql($from, $until,
                               ?string $entityType, ?string $entityCode, ?string $datasource)
    {
        // Use raw sql to ensure the alerts' entities exist in mddb tables
        $em = $this->getEntityManager();
        $rsm = new ResultSetMappingBuilder($em, ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);
        $rsm->addRootEntityFromClassMetadata('App\Entity\Outages\OutagesAlert', 'a');

        $parameters = array_filter(
            [
                (!empty($entityType) ? 'a.meta_type = :type' : null),
                (!empty($entityCode) ? 'a.meta_code = :code' : null),
            ]
        );
        $sql = 'SELECT ' . $rsm->generateSelectClause() . '
            FROM
                watchtower_alert a
               WHERE
                   a.time > :from 
               AND a.time < :until 
               '
            . (!empty($parameters) ?
                ' AND ' . implode(' AND ', $parameters) : '')
            . ' AND EXISTS (SELECT FROM mddb_entity mm WHERE mm.code = a.meta_code) '
            . " ORDER BY a.time ASC "
        ;

        $q = $em->createNativeQuery($sql, $rsm)
            ->setParameters([
                'from' => $from,
                'until' => $until,
                'type' => $entityType,
                'code' => $entityCode,
            ]);

        return $q->getResult();
    }
}
