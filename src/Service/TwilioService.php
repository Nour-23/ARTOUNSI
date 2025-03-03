<?php
namespace App\Service;

use Twilio\Rest\Client;

class TwilioService
{

    private $accountSid;
    private $authToken;
    private $twilioNumber;
    private $client;

    public function __construct()
    {
        $this->accountSid = "ACc1b3957f1240944ab117b1d214a4b34e";
        $this->authToken = "afcb028fcb0ba4c85212cb7e7e74c060";
        $this->twilioNumber = "+12184133359";
        $this->client = new Client($this->accountSid, $this->authToken);
    }

    public function sendSms(string $to, string $body)
    {
        $client =new Client( $this->accountSid,  $this->authToken);
        $message= $client->messages->create(
            $to,
            [
                'from' => $this->twilioNumber,
                'body' => $body,
            ]
        );
    }
}
