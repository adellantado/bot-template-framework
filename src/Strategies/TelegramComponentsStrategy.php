<?php

namespace BotTemplateFramework\Strategies;


use BotMan\BotMan\BotMan;
use BotMan\BotMan\Http\Curl;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\Drivers\Telegram\TelegramDriver;
use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Collection;

class TelegramComponentsStrategy implements IComponentsStrategy, IStrategy {
    protected $bot;

    public function __construct(BotMan $bot) {
        $this->bot = $bot;
    }

    public function getBot() {
        return $this->bot;
    }

    public function reply($message, $additionalParameters = []) {
        $additionalParameters = array_merge($additionalParameters, ['parse_mode' => 'Markdown']);
        return $this->bot->reply($message, $additionalParameters);
    }

    public function sendImage($imageUrl, $text = null) {
        if ($text) {
            $this->sendMenuAndImage($imageUrl, $text, []);
        } else {
            $message = OutgoingMessage::create()->withAttachment(new Image($imageUrl));
            $this->reply($message);
        }
    }

    public function sendMenu($text, array $markup, $options = null) {
        return $this->reply($text, $this->buildMenu($markup));
    }

    public function sendMenuAndImage($imageUrl, $text, array $markup, $options = null) {
        /** @var IncomingMessage $message */
        $message = $this->bot->getMessages()[0];
        $recipient = $message->getRecipient() === '' ? $message->getSender() : $message->getRecipient();
        $this->bot->sendRequest('sendPhoto', array_merge([
            'chat_id' => $recipient,
            'photo' => $imageUrl,
            'caption' => $text
        ], $this->buildMenu([$markup])));
    }

    public function sendList(array $elements, array $globalButton = null, $options = null) {
        foreach ($elements as $item) {
            if (array_key_exists('buttons', $item)) {
                $this->sendMenuAndImage($item['url'], $item['title'], $item['buttons']);
            } else {
                $this->sendImage($item['url'], $item['title']);
            }
        }

        if ($globalButton) {
            $this->sendMenu('', $globalButton);
        }
    }


    public function sendText($text) {
        return $this->reply($text);
    }

    protected function buildMenu(array $markup, $inline = true, $oneTimeKeyboard = false, $resizeKeyboard = false) {
        $menu = [];
        foreach ($markup as $submenu) {
            $rows = [];
            foreach ($submenu as $callback => $title) {
                if (in_array(parse_url($callback, PHP_URL_SCHEME), ['mailto', 'http', 'https', 'tel'])) {
                    $rows[] = KeyboardButton::create($title)->url($callback);
                    continue;
                }
                $rows[] = KeyboardButton::create($title)->callbackData($callback);
            }
            $menu[] = $rows;
        }

        $type = $inline ? Keyboard::TYPE_INLINE : Keyboard::TYPE_KEYBOARD;

        return [
            'reply_markup' => json_encode(Collection::make([
                $type => $menu,
                'one_time_keyboard' => $oneTimeKeyboard,
                'resize_keyboard' => $resizeKeyboard,
            ])->filter()),
        ];
    }

    public function sendCarousel(array $elements, $options = null) {
        $element = $elements[0];
        $text = $element['title'] . PHP_EOL;
        if (array_key_exists('description', $element)) {
            $text .= $element['description'] . PHP_EOL;
        }
        $text .= '[link](' . $element['url'] . ')';
        if (array_key_exists('buttons', $element)) {
            /** @var Response $response */
            $response = $this->sendMenu($text, [
                $element['buttons'],
            ]);
        } else {
            $response = $this->sendText($text);
        }

        $data = json_decode($response->getContent(), true);
        $carouselButtonsLine = [];
        $carouselButtons = [];
        $numberInLine = ceil(count($elements) / ceil(count($elements) / 8));
        foreach ($elements as $index => $element) {
            if ($index % $numberInLine == 0) {
                $carouselButtonsLine[] = $carouselButtons;
                $carouselButtons = [];
            }
            $carouselButtons['carousel_' . $data['result']['message_id'] . '_' . $index] = $index + 1;
        }

        if ($carouselButtons) {
            $carouselButtonsLine[] = $carouselButtons;
        }

        $this->sendMenu('Select button to switch among carousel elements', $carouselButtonsLine);
    }

    /**
     * Method servers only as way of implementing carousel in telegram. No need to call it directly
     *
     * @param BotMan $bot
     * @param $messageId
     * @param $element
     */
    public function carouselSwitch(BotMan $bot, $messageId, $element) {
        $text = $element['title'] . PHP_EOL;
        if (array_key_exists('description', $element)) {
            $text .= $element['description'] . PHP_EOL;
        }
        $text .= '[link](' . $element['url'] . ')';

        /** @var IncomingMessage $message */
        $message = $this->bot->getMessages()[0];
        $recipient = $message->getRecipient() === '' ? $message->getSender() : $message->getRecipient();

        $payload = [
            'chat_id' => $recipient,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ];

        if (array_key_exists('buttons', $element)) {
            $buttons = [];
            foreach ($element['buttons'] as $callback => $title) {
                if (in_array(parse_url($callback, PHP_URL_SCHEME), ['mailto', 'http', 'https', 'tel'])) {
                    $buttons[] = KeyboardButton::create($title)->url($callback);
                    continue;
                }
                $buttons[] = KeyboardButton::create($title)->callbackData($callback);
            }
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => [
                    $buttons
                ],
                'one_time_keyboard' => false,
                'resize_keyboard' => false
            ]);
        }

        $bot->middleware->applyMiddleware('sending', $payload, [], function ($payload) {
            return (new Curl())->post(TelegramDriver::API_URL . $this->bot->getDriver()->getConfig()->get('token') . '/editMessageText',
                [], $payload);
        });
    }

    public function sendQuickButtons($text, array $markup, $options = null) {
        return $this->reply($text, $this->buildMenu($markup, false, $options['one_time_keyboard'] ?? true, $options['resize_keyboard'] ?? false));
    }

    public function sendAudio($url, $text = null) {
        $this->reply(OutgoingMessage::create($text, new Audio($url)));
    }

    public function sendVideo($url, $text = null) {
        $this->reply(OutgoingMessage::create($text, new Video($url)));
    }

    public function sendFile($url, $text = null) {
        $this->reply(OutgoingMessage::create($text, new File($url)));
    }

    public function requireLocation($text, $options = null) {
        return $this->reply($text, [
            'reply_markup' => json_encode([
                Keyboard::TYPE_KEYBOARD => [
                    [[
                        'request_location' => true,
                        'text' =>  $options['title'] ?? 'Share Your Location'
                    ]]
                ],
                'one_time_keyboard' => $options['one_time_keyboard'] ?? true,
                'resize_keyboard' => $options['resize_keyboard'] ?? false,
            ])
        ]);
    }

    public function requireLocationPayload($text, $options = null) {
        $additionalParameters = [
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                Keyboard::TYPE_KEYBOARD => [
                    [[
                        'request_location' => true,
                        'text' => $options['title'] ?? 'Share Your Location'
                    ]]
                ],
                'one_time_keyboard' => $options['one_time_keyboard'] ?? true,
                'resize_keyboard' => $options['resize_keyboard'] ?? false,
            ])];
        return [
            'text' => $text,
            'additionalParameters' => $additionalParameters
        ];
    }

    public function requirePhonePayload($text, $options = null) {
        $additionalParameters = [
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                Keyboard::TYPE_KEYBOARD => [
                    [[
                        'request_contact' => true,
                        'text' => $options['title'] ?? 'Share Your Phone'
                    ]]
                ],
                'one_time_keyboard' => $options['one_time_keyboard'] ?? true,
                'resize_keyboard' => $options['resize_keyboard'] ?? false,
            ])];
        return [
            'text' => $text,
            'additionalParameters' => $additionalParameters
        ];
    }

    public function requireEmailPayload($text, $options = null) {
        return null;
    }


}