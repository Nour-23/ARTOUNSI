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
            ->from(new Address('no-reply@yourdomain.com', 'No Reply'))
            ->to((string) $user->getEmail())
            ->subject('Your password reset request')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken->getToken(),
                'user' => $user, // Pass the user object to the template
            ]);

        $this->mailer->send($email);
    }

    public function sendPasswordChangeConfirmationEmail($user)
    {
        $email = (new Email())
            ->from('no-reply@votre-app.com')  // Remplace ceci par ton adresse email
            ->to($user->getEmail())
            ->subject('Confirmation de changement de mot de passe')
            ->html(
                '<p>Bonjour ' . $user->getName() . ',</p>' .
                '<p>Votre mot de passe a été modifié avec succès. Si vous n\'êtes pas à l\'origine de cette demande, veuillez contacter notre support.</p>' .
                '<p><a href="' . $this->router->generate('app_profile_principale', ['id' => $user->getId()]) . '">Cliquez ici pour accéder à votre profil</a></p>'
            );

        $this->mailer->send($email);
    }
}

