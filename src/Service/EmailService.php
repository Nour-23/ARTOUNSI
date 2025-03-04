<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    private $mailer;
    private $router;

    public function __construct(MailerInterface $mailer, UrlGeneratorInterface $router)
    {
        $this->mailer = $mailer;
        $this->router = $router; // Injection correcte du service router
    }

    public function sendPasswordResetEmail($user, $resetToken): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('arttounsi@art.com', 'No Reply'))
            ->to((string) $user->getEmail())
            ->subject('Your password reset request')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken->getToken(),
                'user' => $user, // Pass the user object to the template
            ]);

        $this->mailer->send($email);
    }

    public function sendPasswordChangeConfirmationEmail($user): void
    {
        // Générer l'URL du profil de l'utilisateur
        $profileUrl = $this->router->generate('app_profile_principale', ['id' => $user->getId()]);
    
        // Créer l'email en utilisant un template Twig
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@votre-app.com', 'No Reply'))
            ->to($user->getEmail())
            ->subject('Confirmation de changement de mot de passe')
            ->htmlTemplate('user/password_change_confirmation.html.twig')  // Utilisation du template
            ->context([
                'user' => $user,
                'profileUrl' => $profileUrl,  // Passer l'URL dans le contexte
            ]);
    
        // Envoyer l'email
        $this->mailer->send($email);
    }
    
}

