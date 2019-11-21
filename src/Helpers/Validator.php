<?php

namespace BotTemplateFramework\Helpers;


use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Attachments\Video;

class Validator {

    public function number($number) {
        if (preg_match('/^[0-9]*$/', $number) == 1) {
            return true;
        }
        return false;
    }

    public function email($email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        return false;
    }

    public function phone($phone) {
        return true;
    }

    public function url($url) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        }
        return false;
    }

    public function image($image) {
        if ($image == Image::PATTERN) {
            return true;
        }
        return false;
    }

    public function file($file) {
        if ($file == File::PATTERN) {
            return true;
        }
        return false;
    }

    public function video($video) {
        if ($video == Video::PATTERN) {
            return true;
        }
        return false;
    }

    public function audio($audio) {
        if ($audio == Audio::PATTERN) {
            return true;
        }
        return false;
    }

    public function location($location) {
        if ($location == Location::PATTERN) {
            return true;
        }
        return false;
    }

    public function regexp($pattern, $text) {
        if (preg_match($pattern, $text) == 1) {
            return true;
        }
        return false;
    }

    public function confirm($oldValue, $newValue) {
        return $newValue === $oldValue;
    }

    public function errorNumberMsg() {
        return 'Please, type valid number (no whitespaces)';
    }

    public function errorEmailMsg() {
        return 'Please, type valid email';
    }

    public function errorPhoneMsg() {
        return 'Please, type valid phone number';
    }

    public function errorUrlMsg() {
        return 'Please, type valid url (start with http:// or https://)';
    }

    public function errorImageMsg() {
        return 'Please, send image';
    }

    public function errorVideoMsg() {
        return 'Please, send video';
    }

    public function errorFileMsg() {
        return 'Please, send file';
    }

    public function errorAudioMsg() {
        return 'Please, send audio';
    }

    public function errorLocationMsg() {
        return 'Please, send your location';
    }

    public function errorRegexpMsg() {
        return 'Please, type valid value';
    }

    public function errorConfirmMsg() {
        return 'Please, type your confirmation input with exact value (case sensitive)';
    }

    public function scenarioWithDriverLimits(array $template) {
        $drivers = $template['drivers'];
        foreach ($template['blocks'] as $block) {
            foreach ($drivers as $driver) {
                $message = $this->blockWithDriverLimits($block, strtolower($driver['name']));
                if ($message) {
                    return $message;
                }
            }
        }
        return null;
    }

    public function blockWithDriverLimits(array $block, $driver) {
        if (array_key_exists('drivers', $block)) {
            $pieces = explode(';', $block['drivers']);
            $driverAllowed = false;
            foreach ($pieces as $piece) {
                if ($piece == '!'.$driver) {
                    return null;
                } elseif ($piece == $driver) {
                    $driverAllowed = true;
                } elseif (in_array($piece, ['any', 'all', '*'])) {
                    $driverAllowed = true;
                }
            }
            if (!$driverAllowed) {
                return null;
            }
        }

        $type = $block['type'];
        if ($driver == 'viber' && $type == 'image') {
            if (array_key_exists('text', $block['content']) && mb_strlen($block['content']['text']) > 120) {
                return 'For image blocks, Viber requires max = 120 characters';
            }
            if (!in_array(pathinfo($block['content']['url'], PATHINFO_EXTENSION), ['jpg', 'jpeg'])) {
                return 'For image blocks, Viber requires only JPEG format';
            }
        } elseif ($driver == 'viber' && $type == 'menu') {
            foreach ($block['content']['buttons'] as $menu) {
                if (count($menu) > 6 || count($menu) == 5 || count($menu) == 4) {
                    return 'Viber buttons mustn\'t be more than 6 in a row. Buttons in amount of 4 or 5 broke UI.';
                }
            }
        } elseif ($driver == 'facebook' && $type == 'menu') {
            if ($block['mode'] == 'quick' && count($block['content']['buttons']) > 10) {
                return 'For menu blocks with quick buttons, Facebook allows to show max = 10 buttons at once';
            } else {
                foreach ($block['content']['buttons'] as $menu) {
                    if (count($menu) > 3) {
                        return 'For menu blocks, Facebook requires max = 3 buttons in a menu';
                    }
                }
            }
        } elseif ($driver == 'facebook' && $type == 'carousel') {
            if (!in_array(($block['options']['image_aspect_ratio'] ?? false), ['horizontal', 'square'])) {
                return 'For Facebook carousel blocks, option \'image_aspect_ratio\' requires to be \'horizontal\' or \'square\'';
            }
            if (count($block['content']) > 10) {
                return 'For carousel blocks, Facebook requires max = 10 elements';
            }

        } elseif ($driver == 'facebook' && $type == 'image') {
            if (count($block['content']['buttons'] ?? []) > 3) {
                return 'For image blocks, Facebook requires max = 3 buttons';
            }
        } elseif ($driver == 'facebook' && $type == 'list') {
            if (count($block['content']) < 2 && count($block['content']) > 4) {
                return 'For list block, Facebook requires min = 2 and max = 4 elements';
            }
        } elseif ($type == 'random') {
            $next = $block['next'];
            $val = 0;
            foreach ($next as $item) {
                $val += (int)$item[0];
                if ($val > 100) {
                    return 'For random block, probability couldn\'t be bigger than 100';
                }
            }
        }

        return null;
    }

    public function isBlockFieldsValid($block) {
        if (!array_key_exists('type', $block)) {
            throw new \Exception('Field=type is required');
        }
        if (!array_key_exists('name', $block)) {
            throw new \Exception('Field=name is required');
        }
        switch ($block['type']) {
            case 'text':
                if (!($block['content'] ?? null)) {
                    throw new \Exception('Field=content is required');
                }
                break;
            case 'image':
                if (!($block['content']['url'] ?? null)) {
                    throw new \Exception('Field=url is required');
                }
                break;
            case 'menu':
                if (!($block['content']['text'] ?? null)) {
                    throw new \Exception('Field=text is required');
                }
                if (!($block['content']['buttons'] ?? null)) {
                    throw new \Exception('Field=buttons is required');
                }
                break;
            case 'audio':
            case 'video':
            case 'file':
                if (!($block['content']['url'] ?? null)) {
                    throw new \Exception('Field=url is required');
                }
                break;
            case 'location':
                if (!($block['content'] ?? null)) {
                    throw new \Exception('Field=content is required');
                }
                if (!($block['result']['save'] ?? null)) {
                    throw new \Exception('Field=save is required');
                }
                break;
            case 'attachment':
                if (!($block['content'] ?? null)) {
                    throw new \Exception('Field=content is required');
                }
                break;
            case 'carousel':
            case 'list':
                if (!($block['content'] ?? null)) {
                    throw new \Exception('Field=content is required');
                }
                break;
            case 'request':
                if (!($block['url'] ?? null)) {
                    throw new \Exception('Field=url is required');
                }
                break;
            case 'ask':
                if (!($block['content'] ?? null)) {
                    throw new \Exception('Field=content is required');
                }
                break;
            case 'intent':
                if (!($block['provider'] ?? null)) {
                    throw new \Exception('Field=provider is required');
                }
                if (!($block['template'] ?? null)) {
                    throw new \Exception('Field=template is required');
                }
                break;
            case 'if':
                break;
            case 'method':
                if (!($block['method'] ?? null)) {
                    throw new \Exception('Field=method is required');
                }
                break;
            case 'extend':
                if (!($block['base'] ?? null)) {
                    throw new \Exception('Field=base is required');
                }
                break;
            case 'save':
                if (!($block['value'] ?? null)) {
                    throw new \Exception('Field=value is required');
                }
                if (!($block['variable'] ?? null)) {
                    throw new \Exception('Field=variable is required');
                }
                break;
            case 'validate':
                if (!($block['validate'] ?? null)) {
                    throw new \Exception('Field=validate is required');
                }
                if (!($block['variable'] ?? null)) {
                    throw new \Exception('Field=variable is required');
                }
                break;
            case 'payload':
                if (!($block['payload'] ?? null)) {
                    throw new \Exception('Field=payload is required');
                }
                break;
            default:
                throw new \Exception('Field=type has wrong value');
        }
    }
}