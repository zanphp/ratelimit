<?php

namespace ZanPHP\RateLimit;

use ZanPHP\Contracts\RateLimit\RateLimiter;

class APCuTokenBucket implements RateLimiter
{
    private $rate;      // 每秒放入令牌数量
    private $capacity;  // 令牌桶容量

    private $key;

    /**
     * APCuTokenBucket constructor.
     * @param $capacity
     * @param $rate
     *
     * ！！！ 必须在父进程实例化
     */
    public function __construct($capacity, $rate)
    {
        $this->key = spl_object_hash($this) . "_token";
        $this->capacity = $capacity;
        $this->rate = intval($rate);

        apcu_store($this->key, $capacity, 0);
    }

    /**
     * ！！！ 某个支持定时器的进程执行
     */
    public function start()
    {
        $this->fillToken();
    }

    private function fillToken()
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        swoole_timer_after(1000, function() {
            while (true) {
                $oldToken = apcu_fetch($this->key);
                $newToken = min($oldToken + $this->rate, $this->capacity);
                if ($oldToken === $newToken) {
                    break;
                }

                if (apcu_cas($this->key, $oldToken, $newToken)) {
                    break;
                }
            }
            $this->fillToken();
        });
    }

    public function acquire($permits = 1)
    {
        $n = 11;
        while (--$n) {
            $oldToken = apcu_fetch($this->key);
            $newToken = $oldToken - $permits;

            if ($newToken < 0) {
                break;
            }

            if (apcu_cas($this->key, $oldToken, $newToken)) {
                return true;
            }
        }
        return false;
    }
}