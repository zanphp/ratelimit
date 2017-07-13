<?php

namespace ZanPHP\RateLimit;

use ZanPHP\Contracts\RateLimit\RateLimiter;


/**
 * Class LeakyBucket
 * 漏桶
 *
 * 常速速率处理请求
 */
class LeakyBucket implements RateLimiter
{
    private $rate;          // 每秒流出水量
    private $capacity;      // 漏桶容量
    private $water;         // 漏桶当前水量
    private $timestamp;

    public function __construct($capacity, $rate)
    {
        $this->capacity = $capacity;
        $this->water = 0;
        $this->rate = $rate;
        $this->timestamp = time();
    }

    public function acquire($permits = 1)
    {
        $now = time();

        // 漏水
        $poured = ($now - $this->timestamp) * $this->rate;
        $this->water = max(0, $this->water - $poured);

        $this->timestamp = $now;

        if ($this->water + $permits <= $this->capacity) {
            $this->water += $permits;
            return true;
        } else {
            return false;
        }
    }
}