<?php

namespace App\Repository;

use App\Entity\Offre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Offre>
 */
class OffreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offre::class);
    }
   // Exemple d'une méthode searchOffers dans le repository
public function searchOffers(?string $title, ?string $category, ?string $status)
{
    $qb = $this->createQueryBuilder('o');

    if ($title) {
        $qb->andWhere('o.title LIKE :title')
           ->setParameter('title', '%' . $title . '%');
    }

    if ($category) {
        $qb->andWhere('o.category = :category')
           ->setParameter('category', $category);
    }

    if ($status) {
        $qb->andWhere('o.status = :status')
           ->setParameter('status', $status);
    }

    return $qb;
}


    //    /**
    //     * @return Offre[] Returns an array of Offre objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Offre
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
