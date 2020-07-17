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
