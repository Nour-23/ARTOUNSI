<?php 
// src/Service/TwilioService.php
namespace App\Service;

use Twilio\Rest\Client;
class TwilioService
{
    private $twilioSid;
    private $twilioAuthToken;
    private $twilioPhoneNumber;

    public function __construct(string $twilioSid, string $twilioAuthToken, string $twilioPhoneNumber)
    {
        $this->twilioSid = $twilioSid;
        $this->twilioAuthToken = $twilioAuthToken;
        $this->twilioPhoneNumber = $twilioPhoneNumber;
    }

    public function sendPasswordResetSms(string $phoneNumber, string $resetToken): void
    {
        $client = new Client($this->twilioSid, $this->twilioAuthToken);
        $message = "Cliquez sur ce lien pour réinitialiser votre mot de passe : https://votre-site.com/reset-password?token=" . $resetToken;

        try {
            $client->messages->create(
                $phoneNumber, // Le numéro du destinataire
                [
                    'from' => $this->twilioPhoneNumber, // Votre numéro Twilio
                    'body' => $message,
                ]
            );
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de l\'envoi du SMS: ' . $e->getMessage());
        }
    }
}