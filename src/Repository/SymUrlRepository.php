<?php

namespace App\Repository;

use App\Entity\SymUrl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method SymUrl|null find($id, $lockMode = null, $lockVersion = null)
 * @method SymUrl|null findOneBy(array $criteria, array $orderBy = null)
 * @method SymUrl[]    findAll()
 * @method SymUrl[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SymUrlRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, SymUrl::class);
    }

//    /**
//     * @return SymUrlFactory[] Returns an array of SymUrlFactory objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SymUrlFactory
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
