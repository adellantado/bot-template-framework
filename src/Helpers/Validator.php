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

    public function errorNumberMsg() {
        return 'Please, type valid number (no whitespaces)';
    }

    public function errorEmailMsg() {
        return 'Please, type valid email';
    }

    public function errorUrlMsg() {
        return 'Please, type valid url (start with http:// or https://)';
    }

}