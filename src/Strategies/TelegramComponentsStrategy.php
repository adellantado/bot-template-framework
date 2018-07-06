<?php

namespace BotTemplateFramework\Strategies;


use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class TelegramComponentsStrategy implements IComponentsStrategy,IStrategy
{
    protected $bot;

    public function __construct(BotMan $bot) {
        $this->bot = $bot;
    }

    public function getBot() {
        return $this->bot;
    }

    public function reply($message, $additionalParameters = []) {
        return $this->bot->reply($message, $additionalParameters);
    }

    public function sendImage($imageUrl, $text = null) {
        if ($text) {
            $this->sendMenuAndImage($imageUrl, $text, []);
        } else {
            $message = OutgoingMessage::create()
                ->withAttachment(new Image($imageUrl));
            $this->reply($message);
        }
    }

    public function sendMenu($text, array $markup) {
        return $this->reply($text, $this->buildMenu($markup));
    }

    public function sendMenuAndImage($imageUrl, $text, array $markup) {
        $recipient = $this->bot->getMessage()->getRecipient() === '' ? $this->bot->getMessage()->getSender() : $this->bot->getMessage()->getRecipient();
        $this->bot->sendRequest('sendPhoto', array_merge(
            [
                'chat_id' => $recipient,
                'photo' => $imageUrl,
                'caption' => $text
            ],
            $this->buildMenu([$markup])
        ));
    }

    public function sendList(array $elements, array $globalButton = null) {
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
        $this->reply($text);
    }

    protected function buildMenu(array $markup) {
        $menu = [];
        foreach($markup as $submenu) {
            $rows = [];
            foreach($submenu as $callback=>$title) {
                if (in_array(parse_url($callback, PHP_URL_SCHEME), ['mailto', 'http', 'https','tel'])) {
                    $rows[] = KeyboardButton::create($title)->url($callback);
                    continue;
                }
                $rows[] = KeyboardButton::create($title)->callbackData($callback);
            }
            $menu[] = $rows;
        }

        return [
            'reply_markup' => json_encode(Collection::make([
                Keyboard::TYPE_INLINE => $menu,
                'one_time_keyboard' => false,
                'resize_keyboard' => false,
            ])->filter()),
        ];
    }

    public function sendCarousel(array $elements)
    {
        $element = $elements[0];
        $text = $element['title'].PHP_EOL.$element['description'].PHP_EOL.$element['url'];
        /** @var Response $response */
        $response = $this->sendMenu($text, [
            $element['buttons'],
        ]);

        $data = json_decode($response->getContent(), true);
        $carouselButtonsLine = [];
        $carouselButtons = [];
        $numberInLine = ceil(count($elements) / ceil(count($elements)/8));
        foreach($elements as $index=>$element) {
            if ($index % $numberInLine == 0) {
                $carouselButtonsLine[] = $carouselButtons;
                $carouselButtons = [];
            }
            $carouselButtons['carousel_'.$data['result']['message_id'].'_'.$index] = $index+1;
        }

        if ($carouselButtons) {
            $carouselButtonsLine[] = $carouselButtons;
        }

        $this->sendMenu('Select button to switch among carousel elements', $carouselButtonsLine);
    }

    public function sendAudio($url, $text = null)
    {
        $this->reply(OutgoingMessage::create($text, new Audio($url)));
    }

    public function sendVideo($url, $text = null)
    {
        $this->reply(OutgoingMessage::create($text, new Video($url)));
    }

    public function sendFile($url, $text = null)
    {
        $this->reply(OutgoingMessage::create($text, new File($url)));
    }

    public function sendLocation()
    {
        // TODO: Implement sendLocation() method.
    }

    public function sendPhone()
    {
        // TODO: Implement sendPhone() method.
    }


}