<?php

namespace App\Repository\Expression;

use App\Entity\Expression\ExpressionFunctionSpecArgument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ExpressionFunctionSpecArgument|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExpressionFunctionSpecArgument|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExpressionFunctionSpecArgument[]    findAll()
 * @method ExpressionFunctionSpecArgument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpressionFunctionSpecArgumentRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ExpressionFunctionSpecArgument::class);
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
