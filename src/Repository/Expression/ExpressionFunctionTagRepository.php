<?php

namespace App\Repository\Expression;

use App\Entity\Expression\ExpressionFunctionTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ExpressionFunctionTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExpressionFunctionTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExpressionFunctionTag[]    findAll()
 * @method ExpressionFunctionTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpressionFunctionTagRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ExpressionFunctionTag::class);
    }

//    /**
//     * @return ExpressionFunctionTag[] Returns an array of ExpressionFunctionTag objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ExpressionFunctionTag
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
