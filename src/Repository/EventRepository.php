<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

/** filter */

    public function findDistinctCategories(): array
    {
        return $this->createQueryBuilder('e')
            ->select('DISTINCT e.eventCategory')
            ->getQuery()
            ->getSingleColumnResult();
    }
    
    /**
     * Méthode pour trouver un événement par son ID
     */
    public function findOneById(int $id): ?Event
    {
        return $this->find($id);
    }

    /**
     * Rechercher des événements par titre ou description
     */
    public function findByTitleOrDescription(string $searchTerm): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.title LIKE :searchTerm')
            ->orWhere('e.description LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('e.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Rechercher des événements par date (avant une certaine date)
     */
    public function findBeforeDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.date < :date')
            ->setParameter('date', $date)
            ->orderBy('e.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
