<?php

namespace CodeIgniter\Settings\Handlers;

use CodeIgniter\Cache\Handlers\RedisHandler;
use Config\Cache;

/**
 * Redis class extension
 */
class BaseRedisHandler extends RedisHandler
{
    public function __construct(Cache $config)
    {
        parent::__construct($config);
        $this->initialize();
    }

    public function getRedis()
    {
        return $this->redis;
    }
}
