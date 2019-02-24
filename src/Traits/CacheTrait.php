<?php

namespace BotTemplateFramework\Traits;

use BotMan\BotMan\Interfaces\CacheInterface;

trait CacheTrait {

    /** @var CacheInterface */
    protected $cache;

    protected function putCacheVariable($name, $value) {
        if ($this->hasSender()) {
            $this->cache->put($name.'-'.$this->getSender(), $value, $this->getOptions()['user_cache_time'] ?? 30);
        }
        return $this;
    }

    protected function getCacheVariable($name) {
        if ($this->hasSender()) {
            return $this->cache->get($name.'-'.$this->getSender(), '');
        }
        return null;
    }

    protected function removeCacheVariable($name) {
        if ($this->hasSender()) {
            return $this->cache->pull($name.'-'.$this->getSender(), '');
        }
        return null;
    }

    protected function hasCacheVariable($name) {
        if ($this->hasSender()) {
            return $this->cache->has($name.'-'.$this->getSender());
        }
        return null;
    }

    protected function hasSender() {
        return (boolean)$this->bot->getMessages();
    }

    protected function getSender() {
        $m = $this->bot->getMessages();
        $sender = $m[0]->getSender();
        if ($this->getDriverName() == 'viber') {
            foreach (['/','\\'] as $index=>$symbol) {
                $sender = str_replace($symbol, '__'.$index.'__', $sender);
            }
        }

        return $sender;
    }

}