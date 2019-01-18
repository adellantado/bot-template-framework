<?php

namespace BotTemplateFramework\Helpers;


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

    public function url($url) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
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

    public function errorUrlMsg() {
        return 'Please, type valid url (start with http:// or https://)';
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
        }

        return null;
    }
}