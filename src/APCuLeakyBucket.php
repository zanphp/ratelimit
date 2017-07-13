<?php

namespace ZanPHP\RateLimit;


use ZanPHP\Contracts\RateLimit\RateLimiter;

class APCuLeakyBucket implements RateLimiter
{
    private $rate;          // 每秒流出水量
    private $capacity;      // 漏桶容量
    private $key;

    public function __construct($capacity, $rate)
    {
        $this->key = spl_object_hash($this) . "_water";
        $this->capacity = $capacity;
        $this->rate = intval($rate);

        apcu_store($this->key, 0, 0);
    }

    /**
     * ！！！ 某个支持定时器的进程执行
     */
    public function start()
    {
        $this->pourWater();
    }

    private function pourWater()
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        swoole_timer_after(1000, function() {
            while (true) {
                $oldWater = apcu_fetch($this->key);
                $newWater = max(0, $oldWater - $this->rate);
                if ($newWater === $oldWater) {
                    break;
                }

                if (apcu_cas($this->key, $oldWater, $newWater)) {
                    break;
                }
            }
            $this->pourWater();
        });
    }

    public function acquire($permits = 1)
    {
        $n = 11;
        while (--$n) {
            $oldWater = apcu_fetch($this->key);
            $newWater = $oldWater + $permits;

            if ($newWater > $this->capacity) {
                break;
            }

            if (apcu_cas($this->key, $oldWater, $newWater)) {
                return true;
            }
        }
        return false;
    }
}