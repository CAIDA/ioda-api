<?php

namespace App\Repository\Expression;

use App\Entity\Expression\ExpressionFunctionArgument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ExpressionFunctionArgument|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExpressionFunctionArgument|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExpressionFunctionArgument[]    findAll()
 * @method ExpressionFunctionArgument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpressionFunctionArgumentRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ExpressionFunctionArgument::class);
    }

//    /**
//     * @return ExpressionFunctionArgument[] Returns an array of ExpressionFunctionArgument objects
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
    public function findOneBySomeField($value): ?ExpressionFunctionArgument
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
