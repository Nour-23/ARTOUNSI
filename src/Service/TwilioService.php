<?php
namespace App\Service;

use Twilio\Rest\Client;
use Psr\Log\LoggerInterface;

class TwilioService
{
    private $client;
    private $twilioNumber;
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        
        $accountSid = $_ENV['TWILIO_SID'];
        $authToken = $_ENV['TWILIO_AUTH_TOKEN'];
        $this->twilioNumber = $_ENV['TWILIO_PHONE_NUMBER'];

        $this->client = new Client($accountSid, $authToken);
    }

    public function sendSms(string $to, string $body): bool
    {
        try {
            $message = $this->client->messages->create(
                $to,
                [
                    'from' => $this->twilioNumber,
                    'body' => $body,
                ]
            );

            $this->logger->info("SMS envoyé avec succès à {$to}. SID: " . $message->sid);
            return true;

        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'envoi du SMS : " . $e->getMessage());
            return false;
        }
    }
}
