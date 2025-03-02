<?php
// src/EventListener/LoginListener.php
// src/EventListener/LoginListener.php
namespace App\EventListener;

use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Psr\Log\LoggerInterface;

class LoginListener
{
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function onInteractiveLogin(AuthenticationEvent $event)
    {
        // Récupère l'utilisateur authentifié
        $user = $event->getAuthenticationToken()->getUser();
        
        if ($user instanceof User) {
            // Log pour vérifier que l'événement est bien déclenché
            $this->logger->info("Event triggered for user: {$user->getName()}");
            
            // Incrémente le compteur de connexions
            $user->setLoginCount($user->getLoginCount() + 1);
            
            // Sauvegarde de l'utilisateur avec le compteur mis à jour
            $this->em->persist($user);
            $this->em->flush();
            
            // Log pour vérifier que le compteur est bien mis à jour
            $this->logger->info("Updated login count for user {$user->getName()}: {$user->getLoginCount()}");
        }
    }
    
}




