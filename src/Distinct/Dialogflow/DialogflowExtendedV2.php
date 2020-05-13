<?php


namespace BotTemplateFramework\Distinct\Dialogflow;


use BotMan\BotMan\BotMan;
use BotMan\BotMan\Http\Curl;
use BotMan\BotMan\Interfaces\HttpInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Middleware\ApiAi;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;

class DialogflowExtendedV2 extends ApiAi {

    protected $keyPath;
    protected $projectId;

    public function __construct($projectId, $keyPath, HttpInterface $http, $lang = 'en-US')
    {
        $this->projectId = $projectId;
        $this->keyPath = $keyPath;
        parent::__construct(null, $http, $lang);
    }

    public static function createV2($projectId, $keyPath, $lang = 'en-US')
    {
        return new static($projectId, $keyPath, new Curl(), $lang);
    }

    public function received(IncomingMessage $message, $next, BotMan $bot)
    {
        $response = $this->getResponse($message);

        if ($response == null) {
            return $next($message);
        }

        $queryResult = $response->getQueryResult();
        $intent = $queryResult->getIntent();

        $reply = $queryResult->getFulfillmentMessages() ?? [];
        $action = $queryResult->getAction() ?? '';
        $actionIncomplete = !$queryResult->getAllRequiredParamsPresent();
        $intent = $intent->getDisplayName() ?? '';
        $parameters = $queryResult->getParameters() ?? [];

        $messages = [];
        $payloads = [];
        foreach ($reply as $item) {
            $payload = $item->getPayload();
            if ($payload) {
                $payloads = array_merge($payloads, json_decode($payload->serializeToJsonString(), true));
            }

            $text = $item->getText();
            if ($text) {
                $texts = $text->getText();
                foreach ($texts as $text) {
                    $messages[] = $text;
                }
            }
        }

        $message->addExtras('apiPayload', $payloads);
        $message->addExtras('apiReply', $messages);
        $message->addExtras('apiAction', $action);
        $message->addExtras('apiActionIncomplete', $actionIncomplete);
        $message->addExtras('apiIntent', $intent);
        $message->addExtras('apiParameters', $parameters);

        return $next($message);
    }

    protected function getResponse(IncomingMessage $message)
    {
        $sessionId = md5($message->getConversationIdentifier());
        return $this->detectIntent($this->projectId, $message->getText(), $sessionId, $this->keyPath, $this->lang);
    }

    protected function detectIntent($projectId, $text, $sessionId, $keyPath, $languageCode = 'en-US')
    {
        // new session
        $test = array('credentials' => $keyPath);
        $sessionsClient = new SessionsClient($test);
        $session = $sessionsClient->sessionName($projectId, $sessionId ?: uniqid());

        // create text input
        $textInput = new TextInput();
        $textInput->setText($text);
        $textInput->setLanguageCode($languageCode);

        // create query input
        $queryInput = new QueryInput();
        $queryInput->setText($textInput);

        $response = null;
        try {
            // get response and relevant info
            $response = $sessionsClient->detectIntent($session, $queryInput);
        } catch(\Exception $e) {
        }
        $sessionsClient->close();
        return $response;
    }

}