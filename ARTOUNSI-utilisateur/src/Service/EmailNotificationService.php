<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailNotificationService
{
    private $mailer;

    // Injection du service MailerInterface
    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    // Fonction pour envoyer un e-mail
    public function sendOfferNotification(string $toEmail, string $offerTitle)
    {
        // Création du message e-mail
        $email = (new Email())
            ->from('no-reply@tonsite.com')  // Adresse d'expédition
            ->to($toEmail)  // L'email destinataire
            ->subject('Nouvelle offre publiée')
            ->html('<p>Une nouvelle offre a été ajoutée : <strong>' . $offerTitle . '</strong></p>');

        // Envoi du message
        $this->mailer->send($email);
    }
}
