<?php
namespace App\Controller;

use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MailController extends AbstractController
{
    #[Route('/send-test-email', name: 'send_test_email')]
    public function sendTestEmail(MailerService $mailerService): Response
    {
        $mailerService->sendEmail('test@example.com', 'Test Subject', 'Hello from Symfony Mailer!');
        return new Response('Email sent successfully!');
    }
}
