<?php

namespace ZanPHP\RateLimit;

use ZanPHP\Contracts\RateLimit\RateLimiter;


/**
 * Class TokenBucket
 * 令牌桶
 *
 * 允许流量流量突发, 桶内令牌可瞬时取走
 */
class TokenBucket implements RateLimiter
{
    private $rate;      // 每秒放入令牌数量
    private $capacity;  // 令牌桶容量
    private $tokens;    // 当前桶内令牌数
    private $timestamp; // 上一次获取令牌时间

    public function __construct($capacity, $rate)
    {
        $this->capacity = $capacity;
        $this->tokens = $capacity;
        $this->rate = intval($rate);
        $this->timestamp = time();
    }

    public function acquire($permits = 1)
    {
        $now = time();

        // 添加令牌
        $filled = ($now - $this->timestamp) * $this->rate;
        $this->tokens = min($this->capacity, $this->tokens + $filled);

        $this->timestamp = $now;

        if ($this->tokens < $permits) {
            return false;
        } else {
            $this->tokens -= $permits;
            return true;
        }
    }
}
