<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class EmailService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
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
}
