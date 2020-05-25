<?php

namespace App\Repository;

use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Company|null find($id, $lockMode = null, $lockVersion = null)
 * @method Company|null findOneBy(array $criteria, array $orderBy = null)
 * @method Company[]    findAll()
 * @method Company[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    public function findInOrder($order)
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.joined', $order)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findByCategory($category)
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.' . $category, 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findByParameter($field, $value, $order)
    {
        return $this->createQueryBuilder('c')
            ->where('c.' . $field . ' = :value')
            ->setParameter('value', $value)
            ->orderBy('c.joined', $order)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findByDate($from, $to, $order)
    {
        return $this->createQueryBuilder('c')
            ->where('c.joined > :from')
            ->andwhere('c.joined < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('c.joined', $order)
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return Company[] Returns an array of Company objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Company
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
