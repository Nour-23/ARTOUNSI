<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    //    /**
    //     * @return Article[] Returns an array of Article objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Article
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function searchArticles(string $query): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.categorie', 'c')
            ->addSelect('c');
    
        if ($query) {
            $qb->where('a.nom LIKE :query')
               ->orWhere('a.description LIKE :query')
               ->orWhere('c.nom LIKE :query')
               ->setParameter('query', '%'.$query.'%');
        }
    
        return $qb->getQuery()->getResult();
    }

    public function sortArticlesByPrix(string $order = 'asc'): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.categorie', 'c')
            ->addSelect('c');
    
        $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';
        $qb->orderBy('a.prix', $order);
    
        return $qb->getQuery()->getResult();
    }
}
