<?php 
// src/Service/SmsService.php
namespace App\Service;

use Twilio\Rest\Client;

class SmsService
{
    private $sid;
    private $authToken;
    private $twilioPhoneNumber;

    public function __construct(string $sid, string $authToken, string $twilioPhoneNumber)
    {
        $this->sid = $sid;
        $this->authToken = $authToken;
        $this->twilioPhoneNumber = $twilioPhoneNumber;
    }

    public function sendSms(string $phoneNumber, string $message)
    {
        $client = new Client($this->sid, $this->authToken);

        $client->messages->create(
            $phoneNumber,  // Numéro de téléphone de l'utilisateur
            [
                'from' => $this->twilioPhoneNumber,  // Ton numéro Twilio
                'body' => $message  // Message que tu veux envoyer
            ]
        );
    }
}
