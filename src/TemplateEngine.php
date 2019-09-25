<?php

namespace BotTemplateFramework;

use BotMan\BotMan\Interfaces\CacheInterface;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Middleware\ApiAi;
use BotMan\BotMan\Middleware\Wit;
use BotTemplateFramework\Builder\Template;
use BotTemplateFramework\Distinct\Chatbase\ChatbaseExtended;
use BotTemplateFramework\Distinct\Dialogflow\DialogflowExtended;
use BotTemplateFramework\Distinct\Dialogflow\DialogflowExtendedV2;
use BotTemplateFramework\Events\Event;
use BotTemplateFramework\Events\ListenStartedEvent;
use BotTemplateFramework\Events\VariableChangedEvent;
use BotTemplateFramework\Events\VariableRemovedEvent;
use BotTemplateFramework\Helpers\Validator;
use BotTemplateFramework\Strategies\StrategyTrait;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Http\Curl;
use BotTemplateFramework\Traits\CacheTrait;
use Opis\Closure\SerializableClosure;

class TemplateEngine {
    use StrategyTrait, CacheTrait;

    /**
     * @var array
     */
    protected $template;

    /**
     * @var BotMan
     */
    protected $bot;

    protected $blockListeners = [];

    protected $eventListeners = [];

    protected $activeBlock;

    protected $botVariables;

    /**
     * @var Wit
     */
    protected $wit;

    /**
     * @var ApiAi
     */
    protected $dialogflow;

    public static function getConfig($template, $config = []) {
        if (is_string($template)) {
            $template = json_decode($template, true);
        } elseif (is_array($template)) {
            // do nothing
        } elseif ($template instanceof Template) {
            $template = $template->jsonSerialize();
        } else {
            throw new \Exception(self::class . " accepts only array, Template or json string");
        }

        // set cache time
        $config['user_cache_time'] = $template['options']['user_cache_time'] ?? 30;
        $config['config'] = [];
        $config['config']['conversation_cache_time'] = $template['options']['conversation_cache_time'] ?? 30;

        foreach ($template['drivers'] as $driver) {
            $driverName = (strtolower($driver['name']) == 'skype' ? 'botframework' : strtolower($driver['name']));
            $config[$driverName] = [];
            if ($driverName == 'web') {
                $config['web'] = [
                    'matchingData' => [
                        'driver' => (array_key_exists('config', $driver) && $driver['config'] == 'true') ?
                            env($driver['token']) : $driver['token'] ?? 'web'
                    ],
                ];
            } else {
                foreach ($driver as $key => $value) {
                    if (!in_array($key, ['name', 'events', 'config'])) {
                        $config[$driverName][$key] = (array_key_exists('config',
                                $driver) && $driver['config'] == 'true') ? env($value) : $value;
                    }
                }
            }
        }

        return $config;
    }

    public function __construct($template, BotMan $bot, CacheInterface $cache = null) {
        $this->bot = $bot;
        $this->setTemplate($template);
        $this->cache = $cache;
        TemplateConversation::$engine = $this;
        if ($this->getDriver('chatbase', false)) {
            ChatbaseExtended::create($this);
        }
    }

    public function setTemplate($template) {
        if (is_string($template)) {
            $this->template = json_decode($template, true);
        } elseif (is_array($template)) {
            $this->template = $template;
        } elseif ($template instanceof Template) {
            $this->template = $template->jsonSerialize();
        } else {
            throw new \Exception(self::class . " accepts only array, Template or json string");
        }

        if ($this->botVariables) {
            $this->template = $this->getParsedTemplate($this->template, $this->botVariables);
        }
    }

    public function setBotVariables($vars) {
        $this->botVariables = $vars;
        if ($this->botVariables && $this->template) {
            $this->template = $this->getParsedTemplate($this->template, $this->botVariables);
        }
    }

    public function getParsedTemplate(array $template, array $variables) {
        if (!$variables) {
            return $template;
        }

        $newTemplate = [];
        foreach ($template as $key=> $value) {
            if (is_array($value)) {
                $newTemplate[$key] = $this->getParsedTemplate($value, $variables);
            } else {
                if (preg_match_all('/{{(.+?)}}/', $value, $matches)) {
                    foreach ($matches[1] as $match) {
                        if (key_exists($match, $variables)) {
                            $value = preg_replace('/{{' . $match . '}}/', $variables[$match], $value);
                        }
                    }
                }
                $newTemplate[$key] = $value;
            }
        }
        return $newTemplate;
    }

    public function setBot(BotMan $bot) {
        $this->bot = $bot;
        return $this;
    }

    /**
     * @return BotMan
     */
    public function getBot() {
        return $this->bot;
    }

    public function getTemplate() {
        return $this->template;
    }

    public function getOptions() {
        return $this->template['options'] ?? [];
    }

    public function getStrategy() {
        return $this->strategy;
    }

    public function getActiveBlock() {
        return $this->activeBlock;
    }

    public function getBotName() {
        return $this->template['name'];
    }

    public function getDefaultLocale() {
        return key_exists('locale', $this->template) ? $this->template['locale'] : 'en';
    }

    public function getDriverName() {
        return strtolower(self::driverName($this->bot));
    }

    /**
     * Adds blocks dynamically to template
     * Note: Call before listen()
     *
     * @param array $blocks
     */
    public function addBlocks(array $blocks) {
        $this->template['blocks'] = array_merge($this->template['blocks'], $blocks);
    }

    /**
     * @param $blockName
     * @param $callback \Closure|array
     * @param bool $capturingPhase
     */
    public function addBlockListener($blockName, $callback, $capturingPhase = false) {
        $this->blockListeners[$blockName] = [
            'callback' => $callback,
            'capturingPhase' => $capturingPhase
        ];
    }

    public function removeBlockListener($blockName) {
        unset($this->blockListeners[$blockName]);
    }

    public function addEventListener($eventName, $callback) {
        $data = [$callback];
        if (array_key_exists($eventName, $this->eventListeners)) {
            $data = array_merge($data, $this->eventListeners[$eventName]);
        }
        $this->eventListeners[$eventName] = $data;
    }

    public function removeEventListener($eventName) {
        unset($this->eventListeners[$eventName]);
    }

    public function dispatchEvent(Event $event) {
        if (array_key_exists($event->getName(), $this->eventListeners)) {
            $callbacks = $this->eventListeners[$event->getName()];
            $result = [];
            foreach ($callbacks as $callback) {
                if ($callback instanceof \Closure) {
                    $result[] = $callback($event, $this);
                } elseif(is_callable($callback)) {
                    $result[] = call_user_func_array($callback, [$event, $this]);
                }
            }
            return $result;
        }
        return true;
    }

    public function getDrivers() {
        return array_map(function ($driver) {
            return $driver['name'];
        }, $this->template['drivers']);
    }


    public function getDriver($name, $loadVariables = true) {
        $filtered = array_filter($this->template['drivers'], function ($driver) use ($name) {
            return strtolower($driver['name']) == strtolower($name);
        });

        if ($filtered) {
            $result = array_values($filtered)[0];
            if ($loadVariables && array_key_exists('config', $result) && $result['config'] == 'true') {
                $driver = [];
                foreach($result as $field=>$value) {
                    if (!in_array($field, ['name', 'events', 'config'])) {
                        $driver[$field] = env($value);
                    } else {
                        $driver[$field] = $value;
                    }
                }
                return $driver;
            }
            return $result;
        }
        return null;
    }

    public function getBlock($name, $locale = null) {
        $filtered = array_filter($this->template['blocks'], function ($block) use ($name, $locale) {
            return $this->validBlock($block) && strtolower($block['name']) == strtolower($name) && ($locale ? (array_key_exists('locale', $block) ? $block['locale'] == $locale : $this->getDefaultLocale() == $locale) : true);
        });

        if ($filtered) {
            return array_values($filtered)[0];
        }
        return null;
    }

    public function listen($callback = null) {
        foreach ($this->template['blocks'] as $block) {
            if (!$this->validBlock($block)) {
                continue;
            }

            if (array_key_exists('template', $block) && $block['type'] != 'intent') {
                $templates = explode(';', $block['template']);
                foreach ($templates as $template) {
                    if ($template) {
                        $this->bot->hears($template, $this->getCallback($block['name'], 'reply_', $callback));
                    }
                }
            }

            if ($block['type'] == 'location' && $this->getCacheVariable('lastBlock') == $block['name']) {
                $this->bot->receivesLocation($this->getCallback($block['name'], 'location_', $callback));
            }

            if ($block['type'] == 'attachment' && $this->getCacheVariable('lastBlock') == $block['name']) {
                if ($block['mode'] == 'image') {
                    $this->bot->receivesImages($this->getCallback($block['name'], 'attachment_image_', $callback));
                } elseif ($block['mode'] == 'video') {
                        $this->bot->receivesVideos($this->getCallback($block['name'], 'attachment_video_', $callback));
                } elseif ($block['mode'] == 'audio') {
                    $this->bot->receivesAudio($this->getCallback($block['name'], 'attachment_audio_', $callback));
                } else {
                    $this->bot->receivesFiles($this->getCallback($block['name'], 'attachment_file_', $callback));
                }
            }

            if ($block['type'] == 'carousel' && $this->getDriverName() == 'telegram') {
                $this->bot->hears('carousel_{messageId}_{index}', $this->getCallback($block['name'], 'carousel_', $callback));
            }

            if ($block['type'] == 'intent') {
                $command = $this->bot->hears($block['template'], $this->getCallback($block['name'], 'reply_', $callback));
                if ($block['provider'] == 'dialogflow') {
                    if (!$this->dialogflow) {
                        $locale = array_key_exists('locale', $block) ? $block['locale'] : $this->getDefaultLocale();
                        $this->initDialogflow(true, $locale);
                        $this->bot->middleware->received($this->dialogflow);
                    }
                    $command->middleware($this->dialogflow);
                } elseif ($block['provider'] == 'wit') {
                    if (!$this->wit) {
                        $this->wit = Wit::create($this->getDriver('wit')['token']);
                        $this->bot->middleware->received($this->wit);
                    }
                    $command->middleware($this->wit);
                }
            }
        }

        $driver = $this->getDriver($this->getDriverName(), false);
        if ($driver && array_key_exists('events', $driver)) {
            foreach ($driver['events'] as $event=>$blockName) {
                $this->bot->on($event, $this->getCallback($blockName, 'reply_', $callback));
            }
        }

        if (array_key_exists('fallback', $this->template)) {
            $this->hearFallback();
        }

        $this->dispatchEvent(new ListenStartedEvent());

        return $this;
    }

    public function __call($name, $arguments) {
        $matches = [];
        if (preg_match('/reply_(.*)/', $name, $matches)) {
            $blockName = preg_replace('/_+/', ' ', $matches[1]);
            $this->reply($blockName);
        } elseif ($this->getDriverName() == 'telegram' && preg_match('/carousel_(.*)/', $name, $matches)) {
            $blockName = preg_replace('/_+/', ' ', $matches[1]);
            $element = $this->getBlock($blockName)['content'][$arguments[2]];
            $this->strategy($this->bot)->carouselSwitch($this->bot, $arguments[1], $element);
        } elseif (preg_match('/location_(.*)/', $name, $matches)) {
            $blockName = preg_replace('/_+/', ' ', $matches[1]);
            $block = $this->getBlock($blockName);
            /** @var Location $location */
            $location = $arguments[1];
            $this->saveVariable($block['result']['save'], [
                'latitude'=> $location->getLatitude(),
                'longitude'=> $location->getLongitude()
            ]);
            $this->removeCacheVariable('lastBlock');
            if ($this->callListener($block) === false) {
                return $this;
            }
            if ($this->checkNextBlock($block)) {
                $this->executeNextBlock($block);
            }
        } elseif (preg_match('/attachment_(image|file|video|audio)_(.*)/', $name, $matches)) {
            $blockName = preg_replace('/_+/', ' ', $matches[2]);
            $block = $this->getBlock($blockName);
            $url = $arguments[1][0]->getUrl();
            $this->saveVariable($block['result']['save'], $url);
            $this->removeCacheVariable('lastBlock');
            if ($this->callListener($block) === false) {
                return $this;
            }
            if ($this->checkNextBlock($block)) {
                $this->executeNextBlock($block);
            }
        }
    }

    public function reply($blockName) {
        return $this->executeBlock($this->getBlock($blockName));
    }

    public function executeBlock($block) {
        if (!$block || !$this->validBlock($block)) {
            return $this;
        }

        $this->activeBlock = $block;

        $type = $block['type'];
        $content = array_key_exists('content', $block) ? $block['content'] : null;
        $result = null;

        if (array_key_exists('typing', $block)) {
            $this->bot->typesAndWaits((int)$block['typing']);
        }

        if ($this->callListener($block, true) === false) {
            return $this;
        }

        if ($type == 'text') {
            $this->strategy($this->bot)->sendText($this->getText($content), $block['options'] ?? null);
        } elseif ($type == 'image') {
            if (array_key_exists('buttons', $content)) {
                $this->strategy($this->bot)->sendMenuAndImage($this->parseText($content['url']),
                    $this->getText($content['text']), $this->parseArray($content['buttons']), $block['options'] ?? null);
            } else {
                $this->strategy($this->bot)->sendImage($this->parseText($content['url']),
                    array_key_exists('text', $content) ? $this->getText($content['text']) : null, $block['options'] ?? null);
            }
        } elseif ($type == 'video') {
            $this->strategy($this->bot)->sendVideo($this->parseText($content['url']),
                array_key_exists('text', $content) ? $this->getText($content['text']) : null,
                $block['options'] ?? null);
        } elseif ($type == 'audio') {
            $this->strategy($this->bot)->sendAudio($this->parseText($content['url']),
                array_key_exists('text', $content) ? $this->getText($content['text']) : null,
                $block['options'] ?? null);
        } elseif ($type == 'file') {
            $this->strategy($this->bot)->sendFile($this->parseText($content['url']),
                array_key_exists('text', $content) ? $this->getText($content['text']) : null,
                $block['options'] ?? null);
        } elseif ($type == 'menu') {
            if (array_key_exists('mode', $block) && $block['mode'] == 'quick') {
                $this->strategy($this->bot)->sendQuickButtons($this->getText($content['text']),
                    $this->parseArray($content['buttons']), $block['options'] ?? null);
            } else {
                $this->strategy($this->bot)->sendMenu($this->getText($content['text']),
                    $this->parseArray($content['buttons']), $block['options'] ?? null);
            }
        } elseif ($type == 'list') {
            $this->strategy($this->bot)->sendList($this->parseArray($content), null, $block['options'] ?? null);
        } elseif ($type == 'carousel') {
            $this->strategy($this->bot)->sendCarousel($this->parseArray($content), $block['options'] ?? null);
        } elseif ($type == 'location') {
            $this->strategy($this->bot)->requireLocation($this->getText($content), $block['options'] ?? null);
        } elseif ($type == 'attachment') {
            $this->strategy($this->bot)->sendText($this->getText($content), $block['options'] ?? null);
        } elseif ($type == 'request') {
            $result = $this->executeRequest($block);
        } elseif ($type == 'method') {
            call_user_func([$this->strategy($this->bot), $block['method']]);
        } elseif ($type == 'ask') {
            $this->executeAsk($block);
        } elseif ($type == 'intent') {
            $result = $this->executeIntent($block);
        } elseif ($type == 'extend') {
            $this->executeExtend($block);
        } elseif ($type == 'if') {
            $this->executeIf($block);
        } elseif ($type == 'random') {
            $this->executeRandom($block);
        } elseif ($type == 'idle') {
            // does nothing
        } elseif ($type == 'save') {
            $this->executeSave($block);
        } elseif ($type == 'payload') {
            $this->strategy($this->bot)->sendPayload($block['payload']);
        } elseif ($type == 'validate') {
            $this->executeValidate($block);
        } else {
            throw new \Exception('Can\'t find any suitable block type');
        }

        if (!in_array($block['type'], ['ask', 'attachment', 'location'])) {
            if ($this->callListener($block) === false) {
                return $this;
            }
        }

        // store location or attachment block name to call receive attachment method on a next session
        if (in_array($block['type'], ['location', 'attachment'])) {
            $this->putCacheVariable('lastBlock', $block['name']);
        }

        $this->activeBlock = null;

        if ($this->checkNextBlock($block) && !in_array($block['type'], ['ask', 'extend', 'if', 'random', 'location', 'attachment', 'validate'])) {
            $this->executeNextBlock($block, $result);
        }
        return $this;
    }

    public function checkNextBlock($block) {
        return array_key_exists('next', $block);
    }

    public function executeNextBlock($currentBlock, $key = null) {
        if (is_array($currentBlock['next'])) {
            if ($key && array_key_exists($key, $currentBlock['next'])) {
                $this->executeBlock($this->getBlock($currentBlock['next'][$key],
                    array_key_exists('locale', $currentBlock) ? $currentBlock['locale'] : null));
            } elseif (array_key_exists('fallback', $currentBlock['next'])) {
                $this->executeBlock($this->getBlock($currentBlock['next']['fallback'],
                    array_key_exists('locale', $currentBlock) ? $currentBlock['locale'] : null));
            }
        } else {
            $this->executeBlock($this->getBlock($currentBlock['next'],
                array_key_exists('locale', $currentBlock) ? $currentBlock['locale'] : null));
        }
    }

    public function saveVariable($name, $value) {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $matches = [];
        if (preg_match_all('/{{(.+?)}}/', $name, $matches)) {
            $name = $matches[1][0];
        }
        $data = $this->bot->userStorage()->find()->toArray();
        $data[$name] = $value;
        $this->bot->userStorage()->save($data);
        $this->dispatchEvent(new VariableChangedEvent($name, $value));
    }

    public function getVariable($name) {
        switch ($name) {
            case 'user.id':
                return $this->bot->getUser()->getId();
            case 'user.firstName':
                return $this->bot->getUser()->getFirstName();
            case 'user.lastName':
                return $this->bot->getUser()->getLastName();
            case 'bot.name':
                return $this->getBotName();
            case 'bot.driver':
                return self::driverName($this->bot);
            case 'message':
                return $this->bot->getMessage()->getText();
        }

        if ($value = $this->bot->userStorage()->get($name)) {
            return $value;
        }


        $keys = explode('.', $name);
        if ($keys) {
            $value = $this->bot->userStorage()->get($keys[0]);
            if (count($keys) > 1) {
                if ($res = json_decode($value, true)) {
                    $value = $res;
                    try {
                        for($i = 1; $i < count($keys); $i++) {
                            $value = $value[$keys[$i]];
                        }
                    } catch(\Exception $e) {
                        $value = '';
                    }
                } else {
                    $value = '';
                }
            }
        } else {
            $value = '';
        }

        return $value;
    }

    public function removeVariable($name) {
        $data = $this->bot->userStorage()->find()->toArray();
        $value = $data[$name];
        unset($data[$name]);
        $this->bot->userStorage()->save($data);
        $this->dispatchEvent(new VariableRemovedEvent($name));
        return $value;
    }

    public function callListener($block, $capturingPhase = false) {
        if (array_key_exists($block['name'], $this->blockListeners)) {
            $event = $this->blockListeners[$block['name']];
            if ($event['capturingPhase'] === $capturingPhase) {
                $callback = $event['callback'];
                if ($callback instanceof \Closure) {
                    return $callback($this, $block);
                } elseif(is_callable($callback)) {
                    return call_user_func_array($callback, [$this, $block]);
                }
            }
        }

        return true;
    }

    public function parseText($text) {
        $matches = [];
        if (preg_match_all('/{{(.+?)}}/', $text, $matches)) {
            foreach ($matches[1] as $match) {
                $text = preg_replace('/{{' . $match . '}}/', $this->getVariable($match), $text);
            }
        }
        return $text;
    }

    public function getText($text) {
        return $this->parseText($this->getRandomResponseText($text));
    }

    protected function parseArray($array) {
        $result = [];
        foreach ($array as $key => $item) {
            if (is_array($item)) {
                $result[$key] = $this->parseArray($item);
            } else {
                $result[$this->parseText($key)] = $this->parseText($item);
            }
        }
        return $result;
    }

    protected function getSubVariable(string $name, array $data) {
        $value = '';
        $keys = explode('.', $name);
        try {
            foreach($keys as $name) {
                $data = $data[$name];
            }
            $value = is_string($data) ? $data : '';
        } catch(\Exception $e) {
        }
        return $value;
    }

    protected function executeRequest($block) {
        $response = null;
        try {
            $client = new Curl();
            $json = [];
            if (array_key_exists('body', $block)) {
                $json = $this->parseArray($block['body']);
            }
            $headers = [];
            if (array_key_exists('headers', $block)) {
                foreach ($block['headers'] as $type=>$value) {
                    $headers[] = $type.': '.$value;
                }
            }
            if (strtolower($block['method']) == "get") {
                $response = $client->get($this->parseText($block['url']), $json, $headers, true);
            } elseif (strtolower($block['method']) == "post") {
                $response = $client->post($this->parseText($block['url']), [], $json, $headers, true);
            }
        } catch (\Exception $e) {
            return null;
        }

        if ($response && $response->getStatusCode() == 200) {
            $result = json_decode($response->getContent(), true);
            if (array_key_exists('result', $block)) {
                if (array_key_exists('field', $block['result'])) {
                    $result = $this->getSubVariable($block['result']['field'], $result);
                }

                if (array_key_exists('save', $block['result'])) {
                    $this->saveVariable($block['result']['save'], $result);
                }
            }

            return $result;
        }

        return null;
    }

    protected function executeAsk($block) {
        $conversation = new TemplateConversation();
        $conversation->blockName = $block['name'];
        $this->bot->startConversation($conversation);
    }

    protected function executeIntent($block) {
        $result = null;
        if ($block['provider'] == 'alexa') {
            if (array_key_exists('result', $block)) {
                $slots = $this->bot->getMessage()->getExtras('slots');
                $result = [];
                foreach($slots as $slot) {
                    $result[$slot['name']] = $slot['value'];
                }

                if (array_key_exists('field', $block['result'])) {
                    $result = $this->getSubVariable($block['result']['field'], $result);
                }

                if (array_key_exists('save', $block['result'])) {
                    $this->saveVariable($block['result']['save'], $result);
                }

            }
            if (array_key_exists('content', $block)) {
                $this->bot->reply($this->getText($block['content']));
            }
        } elseif ($block['provider'] == 'dialogflow') {
            if (array_key_exists('result', $block)) {
                $result = $this->bot->getMessage()->getExtras()['apiParameters'];

                if (array_key_exists('field', $block['result'])) {
                    $result = $this->getSubVariable($block['result']['field'], $result);
                }

                if (array_key_exists('save', $block['result'])) {
                    $this->saveVariable($block['result']['save'], $result);
                }

            }
            if (array_key_exists('content', $block)) {
                $this->bot->reply($this->getText($block['content']));
            } else {
                $extras = $this->bot->getMessage()->getExtras();
                foreach($extras['apiReply'] as $speech) {
                    $this->bot->reply($speech);
                }
            }
        } elseif ($block['provider'] == 'wit') {
            if (array_key_exists('result', $block)) {
                $result = $this->bot->getMessage()->getExtras()['entities'];

                if (array_key_exists('save', $block['result'])) {
                    foreach ($result as $name=>$item) {
                        if ($block['result']['field'] == $name) {
                            $result = $item['value'];
                            $this->saveVariable($block['result']['save'], $result);
                        }
                    }
                }

            }
            if (array_key_exists('content', $block)) {
                $this->bot->reply($this->getText($block['content']));
            }
        }

        return $result;
    }

    /**
     * Note:
     * - do not extend blocks - 'intent' and 'ask';
     * - extend only fields of 1st level;
     *
     * @param $block
     * @throws \Exception
     */
    protected function executeExtend($block) {
        $baseBlock = $this->getBlock($block['base']);
        foreach ($block as $field=>$value) {
            if (!in_array($field, ['name', 'base', 'type'])) {
                $baseBlock[$field] = $value;
            }
        }
        $this->executeBlock($baseBlock);
    }

    protected function executeIf($block) {
        $eqs = $block['next'];
        foreach($eqs as $eq) {
            $a = $this->parseText($eq[0]);
            $b = $this->parseText($eq[2]);
            $op = $eq[1];
            if (
                ($op == '==' && $a == $b) ||
                ($op == '!=' && $a != $b) ||
                ($op == '>'  && $a >  $b) ||
                ($op == '<'  && $a <  $b) ||
                ($op == '>=' && $a >= $b) ||
                ($op == '<=' && $a <= $b)
            ) {
                $this->executeBlock($this->getBlock($eq[3]));
                return;
            }
        }
    }

    protected function executeRandom($block) {
        $eqs = $block['next'];
        $rand = rand(0, 100);
        $value = 0;
        foreach($eqs as $eq) {
            $value += (int)$this->parseText($eq[0]);
            if ($value >= $rand) {
                $this->executeBlock($this->getBlock($eq[1]));
                return;
            }
        }
    }

    protected function executeSave($block) {
        $this->saveVariable($block['variable'], $this->parseText($block['value']));
    }

    protected function executeValidate($block){
        $validator = new Validator();
        $var = $this->parseText($block['variable']);
        if ($block['validate'] == 'number') {
            if (!$validator->number($var)) {
                $this->executeBlock($this->getBlock($block['next']['false']));
                return;
            }
        } elseif ($block['validate'] == 'url') {
            if (!$validator->url($var)) {
                $this->executeBlock($this->getBlock($block['next']['false']));
                return;
            }
        } elseif ($block['validate'] == 'email') {
            if (!$validator->email($var)) {
                $this->executeBlock($this->getBlock($block['next']['false']));
                return;
            }
        } elseif ($block['validate'] == 'phone') {
            if (!$validator->phone($var)) {
                $this->executeBlock($this->getBlock($block['next']['false']));
                return;
            }
        } elseif ($block['validate'] == 'image') {
            if (!$validator->image($var)) {
                $this->executeBlock($this->getBlock($block['next']['false']));
                return;
            }
        } elseif ($block['validate'] == 'file') {
            if (!$validator->file($var)) {
                $this->executeBlock($this->getBlock($block['next']['false']));
                return;
            }
        } elseif ($block['validate'] == 'video') {
            if (!$validator->video($var)) {
                $this->executeBlock($this->getBlock($block['next']['false']));
                return;
            }
        } elseif ($block['validate'] == 'audio') {
            if (!$validator->audio($var)) {
                $this->executeBlock($this->getBlock($block['next']['false']));
                return;
            }
        } elseif ($block['validate'] == 'location') {
            if (!$validator->location($var)) {
                $this->executeBlock($this->getBlock($block['next']['false']));
                return;
            }
        } else {
            if (!$validator->regexp($block['validate'], $var)) {
                $this->executeBlock($this->getBlock($block['next']['false']));
                return;
            }
        }
        $this->executeBlock($this->getBlock($block['next']['true']));
    }

    protected function getCallback($blockName, $prefix, $callback = null) {
        if ($callback) {
            return $callback;
        }
        $blockName = preg_replace('/\s+/', '_', $blockName);
        return [$this, $prefix . $blockName];
    }

    protected function hearFallback() {
        $this->bot->fallback(function (BotMan $bot) {
            $fallback = $this->template['fallback'];
            if (is_string($fallback)) {
                $this->strategy($this->bot)->sendText($this->parseText($fallback));
            } elseif (is_array($fallback)) {
                if ($fallback['type'] == 'block') {
                    $this->executeBlock($this->getBlock($fallback['name']));
                } elseif ($fallback['type'] == 'dialogflow') {
                    $this->initDialogflow(false);
                    $this->dialogflow->received($bot->getMessages()[0],
                        function(IncomingMessage $message) use ($fallback){
                            $extras = $message->getExtras();
                            if ($extras) {
                                if ($extras['apiReply']) {
                                    foreach($extras['apiReply'] as $speech) {
                                        $this->strategy($this->bot)->sendText($speech);
                                    }
                                }
                                if ($extras['apiPayload']) {
                                    foreach($extras['apiPayload'] as $key=>$value) {
                                        if ($key == 'next') {
                                            $this->reply($value);
                                        }
                                    }
                                }
                            } elseif (key_exists('default', $fallback)) {
                                $this->strategy($this->bot)->sendText(
                                    $fallback['default']
                                );
                            }
                        }, $bot);
                }
            }
        });
    }

    protected function validBlock($block) {
        $valid = false;
        if (array_key_exists('drivers', $block)) {
            $drivers = explode(';', $block['drivers']);
            foreach ($drivers as $driver) {
                if (strtolower($driver) == '!' . $this->getDriverName()) {
                    return false;
                }

                if ($driver == '*' || strtolower($driver) == 'any' || strtolower($driver) == $this->getDriverName()) {
                    $valid = true;
                }
            }
        } else {
            $valid = true;
        }
        return $valid;
    }

    protected function getRandomResponseText($text) {
        $texts = explode(';', $text);
        return $texts[array_rand($texts)];
    }

    protected function initDialogflow($listenForAction, $locale = null){
        if (!$this->dialogflow) {
            $driver = $this->getDriver('dialogflow');
            $version = $driver['version'] ?? '1';
            if ($version == '2') {
                $this->dialogflow = DialogflowExtendedV2::createV2($driver['project_id'], $driver['key_path'], $locale ?? $this->getDefaultLocale());
            } else {
                $this->dialogflow = DialogflowExtended::create($driver['token'], $locale ?? $this->getDefaultLocale());
            }

        }

        if ($listenForAction) {
            $this->dialogflow->listenForAction();
        }
    }

    public function __wakeup() {
        foreach($this->blockListeners as $blockName=>&$callback) {
            $callback = unserialize($callback);
        }
    }

    /**
     * @return array
     */
    public function __sleep() {
        foreach($this->blockListeners as $blockName=>&$callback) {
            if ($callback instanceof \Closure) {
                $callback = serialize(new SerializableClosure($callback, true));
            }
        }
        return [
            'template',
            'listeners'
        ];
    }
}