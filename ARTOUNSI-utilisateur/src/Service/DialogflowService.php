<?php

namespace App\Service; // <- Vérifie que c'est exactement "App\Service"

use Google\Cloud\Dialogflow\V2\Client\SessionsClient; 
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\Cloud\Dialogflow\V2\QueryResult;
use Google\Cloud\Dialogflow\V2\DetectIntentRequest;  
use Google\Cloud\Core\ExponentialBackoff;

class DialogflowService
{
    private $projectId;
    private $sessionsClient;

    public function __construct(string $projectId)
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . getenv('GOOGLE_APPLICATION_CREDENTIALS'));
        putenv('GOOGLE_APPLICATION_CREDENTIALS=C:/Users/asma/Downloads/aaaa-m1did3-1c4573823726.json');
        $this->projectId = $projectId;
        $this->sessionsClient = new SessionsClient();
    }
    public function startConversation($text) {
        return $this->detectIntentText($text);
    }
    

    // src/Service/DialogflowService.php

public function sendMessage($message)
{
    try {
        $sessionId = uniqid();
        $session = $this->sessionsClient->sessionName($this->projectId, $sessionId);

        $textInput = new TextInput();
        $textInput->setText($message);
        $textInput->setLanguageCode('fr');

        $queryInput = new QueryInput();
        $queryInput->setText($textInput);

        // Crée un objet DetectIntentRequest
        $detectIntentRequest = new DetectIntentRequest();
        $detectIntentRequest->setSession($session);
        $detectIntentRequest->setQueryInput($queryInput);

        $response = $this->sessionsClient->detectIntent($detectIntentRequest);
        // Récupère la réponse
        $queryResult = $response->getQueryResult();

        return $queryResult->getFulfillmentText();
    } catch (\Exception $e) {
        return "Erreur lors de la communication avec Dialogflow : " . $e->getMessage();
    }
}


    public function detectIntentText($text)
    {
        try {
            $sessionId = uniqid();
            $session = $this->sessionsClient->sessionName($this->projectId, $sessionId);

            $textInput = new TextInput();
            $textInput->setText($text);
            $textInput->setLanguageCode('fr');

            $queryInput = new QueryInput();
            $queryInput->setText($textInput);

            // Crée un objet DetectIntentRequest
            $detectIntentRequest = new DetectIntentRequest();
            $detectIntentRequest->setSession($session);
            $detectIntentRequest->setQueryInput($queryInput);

            $response = $this->sessionsClient->detectIntent($detectIntentRequest);
            // Récupère la réponse
            $queryResult = $response->getQueryResult();

            return $queryResult->getFulfillmentText();
        } catch (\Exception $e) {
            return "Erreur lors de la communication avec Dialogflow : " . $e->getMessage();
        }
        
    }



    public function getResponse(string $message): string
    {
        $sessionClient = new SessionsClient();
        $session = $sessionClient->sessionName($this->projectId, uniqid());
        $textInput = new TextInput();
        $textInput->setText($message);
        $textInput->setLanguageCode($this->languageCode);
        
        $queryInput = new QueryInput();
        $queryInput->setText($textInput);
        
        $response = $sessionClient->detectIntent($session, $queryInput);
        
        $queryResult = $response->getQueryResult();
        
        return $queryResult->getFulfillmentText(); // Récupère la réponse du chatbot
    }
}
