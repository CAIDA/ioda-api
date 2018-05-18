<?php

namespace App\Repository\Expression;

use App\Entity\Expression\ExpressionFunctionSpec;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ExpressionFunctionSpec|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExpressionFunctionSpec|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExpressionFunctionSpec[]    findAll()
 * @method ExpressionFunctionSpec[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpressionFunctionSpecRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ExpressionFunctionSpec::class);
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
