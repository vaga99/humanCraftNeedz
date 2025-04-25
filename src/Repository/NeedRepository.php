<?php

namespace App\Repository;

use App\Entity\Need;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Need>
 */
class NeedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Need::class);
    }

       /**
        * Return all need object filtered by title
        * 
        * @return Need[] Returns an array of Need objects
        */
       public function findByTitle($value): array
       {
           return $this->createQueryBuilder('n')
               ->andWhere('n.title LIKE :val')
               ->setParameter('val', '%'.$value."%")
               ->orderBy('n.id', 'ASC')
               ->getQuery()
               ->getResult()
           ;
       }

    //    public function findOneBySomeField($value): ?Need
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
