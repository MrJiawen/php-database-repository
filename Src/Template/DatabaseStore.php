<?php

namespace App\Store;

/**
 * Class DatabaseStore
 * @package App\Model
 */
class DatabaseStore extends Cache\DatabaseCacheStore
{
    public function __construct()
    {
        parent::__construct();

        /**
         *  set all index what it's driver
         */
        $this->string_key_driver = config('database_repository.string_key_driver');
        $this->list_key_driver = config('database_repository.list_key_driver');
        $this->hash_key_driver = config('database_repository.hash_key_driver');

        /**
         *  set all index what it's expiration
         */
        $this->string_key_expiration = config('database_repository.short_expiration');
        $this->list_key_expiration = config('database_repository.short_expiration');
        $this->hash_key_expiration = config('database_repository.short_expiration');
    }
}