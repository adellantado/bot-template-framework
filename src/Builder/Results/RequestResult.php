<?php

namespace BotTemplateFramework\Builder\Results;


class RequestResult extends Result {

    protected $field;

    public function field(array $field) {
        $this->field = $field;
        return $this;
    }

    public function toArray() {
        if ($this->field) {
            $array = [
                'field' => implode('.', $this->field),
            ];
            return array_merge(parent::toArray(), $array);
        }

        return parent::toArray();
    }

}