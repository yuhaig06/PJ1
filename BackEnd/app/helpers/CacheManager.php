<?php

namespace App\Helpers;

class CacheManager
{
    private static $instance = null;
    private $redis;
    private $config;
    private $prefix;

    private function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        $this->prefix = $this->config['cache']['prefix'];
        $this->initializeRedis();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeRedis()
    {
        try {
            $this->redis = new \Redis();
            $this->redis->connect(
                $this->config['cache']['redis']['host'],
                $this->config['cache']['redis']['port']
            );
            if (!empty($this->config['cache']['redis']['password'])) {
                $this->redis->auth($this->config['cache']['redis']['password']);
            }
        } catch (\Exception $e) {
            error_log("Redis connection failed: " . $e->getMessage());
            $this->redis = null;
        }
    }

    public function get($key)
    {
        if (!$this->redis) {
            return null;
        }

        $value = $this->redis->get($this->prefix . $key);
        return $value ? json_decode($value, true) : null;
    }

    public function set($key, $value, $ttl = 3600)
    {
        if (!$this->redis) {
            return false;
        }

        return $this->redis->setex(
            $this->prefix . $key,
            $ttl,
            json_encode($value)
        );
    }

    public function delete($key)
    {
        if (!$this->redis) {
            return false;
        }

        return $this->redis->del($this->prefix . $key);
    }

    public function clear()
    {
        if (!$this->redis) {
            return false;
        }

        $keys = $this->redis->keys($this->prefix . '*');
        if (!empty($keys)) {
            return $this->redis->del($keys);
        }
        return true;
    }

    public function remember($key, $ttl, $callback)
    {
        $value = $this->get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function increment($key, $value = 1)
    {
        if (!$this->redis) {
            return false;
        }

        return $this->redis->incrBy($this->prefix . $key, $value);
    }

    public function decrement($key, $value = 1)
    {
        if (!$this->redis) {
            return false;
        }

        return $this->redis->decrBy($this->prefix . $key, $value);
    }

    public function exists($key)
    {
        if (!$this->redis) {
            return false;
        }

        return $this->redis->exists($this->prefix . $key);
    }

    public function getMultiple($keys)
    {
        if (!$this->redis) {
            return [];
        }

        $prefixedKeys = array_map(function($key) {
            return $this->prefix . $key;
        }, $keys);

        $values = $this->redis->mGet($prefixedKeys);
        $result = [];

        foreach ($keys as $i => $key) {
            $result[$key] = $values[$i] ? json_decode($values[$i], true) : null;
        }

        return $result;
    }

    public function setMultiple($values, $ttl = 3600)
    {
        if (!$this->redis) {
            return false;
        }

        $pipeline = $this->redis->multi();
        foreach ($values as $key => $value) {
            $pipeline->setex(
                $this->prefix . $key,
                $ttl,
                json_encode($value)
            );
        }
        return $pipeline->exec();
    }

    public function deleteMultiple($keys)
    {
        if (!$this->redis) {
            return false;
        }

        $prefixedKeys = array_map(function($key) {
            return $this->prefix . $key;
        }, $keys);

        return $this->redis->del($prefixedKeys);
    }

    public function getStats()
    {
        if (!$this->redis) {
            return null;
        }

        return [
            'info' => $this->redis->info(),
            'memory' => $this->redis->info('memory'),
            'keys' => $this->redis->dbSize(),
            'last_save' => $this->redis->lastSave()
        ];
    }
} 