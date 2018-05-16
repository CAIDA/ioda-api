<?php

namespace App\Repository\Expression;

use App\Entity\Expression\ExpressionFunction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ExpressionFunction|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExpressionFunction|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExpressionFunction[]    findAll()
 * @method ExpressionFunction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpressionFunctionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ExpressionFunction::class);
    }

//    /**
//     * @return ExpressionFunction[] Returns an array of ExpressionFunction objects
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
    public function findOneBySomeField($value): ?ExpressionFunction
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
