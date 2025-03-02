<?php
// src/EventListener/AuthenticationListener.php

namespace App\EventListener;

use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class AuthenticationListener
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function onAuthenticationSuccess(AuthenticationEvent $event)
    {
        // Récupère l'utilisateur authentifié
        $user = $event->getAuthenticationToken()->getUser();

        // Vérifie que l'utilisateur est une instance de la classe User
        if ($user instanceof User) {
            // Incrémente le compteur de connexions
            $user->setLoginCount($user->getLoginCount() + 1);

            // Sauvegarde de l'utilisateur avec le compteur mis à jour
            $this->em->persist($user);
            $this->em->flush();
        }
    }
}




