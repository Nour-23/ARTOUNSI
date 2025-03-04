<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user, bool $flush = true): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($user);
        if ($flush) {
            $entityManager->flush();
        }}
    
    public function findUserByNsc(string $name): array
{
           return $this->createQueryBuilder('s')
           ->where('s.name LIKE :name')
           ->setParameter('name', '%'.$name.'%')
           ->getQuery()
           ->getResult();
}
}