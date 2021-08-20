<?php


namespace IotSpace\Common;


interface CacheInterface
{
    static function get(string $key, mixed $default = null);

    static function has(string $key);

    static function pull(string $key, mixed $default = null);
}
