<?php

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    /**
     * Récupérer toutes les formations triées par date de création (du plus récent au plus ancien)
     * @return Formation[]
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.dateCreation', 'DESC') // Remplace 'dateCreation' par le vrai champ
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver une formation par son titre (exemple d'utilisation d'un paramètre)
     * @param string $titre
     * @return Formation|null
     */
    public function findByTitle(string $titre): ?Formation
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.titre = :titre')
            ->setParameter('titre', $titre)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouver toutes les formations actives
     * @return Formation[]
     */
    public function findActiveFormations(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.active = :status')
            ->setParameter('status', true)
            ->orderBy('f.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Rechercher des formations par mot-clé dans le titre ou la description
     * @param string $keyword
     * @return Formation[]
     */
    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.titre LIKE :keyword OR f.description LIKE :keyword')
            ->setParameter('keyword', '%'.$keyword.'%')
            ->orderBy('f.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
