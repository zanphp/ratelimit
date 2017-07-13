<?php

namespace ZanPHP\RateLimit;

use ZanPHP\Contracts\RateLimit\RateLimiter;


/**
 * Class RedisRateLimit
 *
 * 分布式限流
 */
class RedisRateLimit implements RateLimiter
{
    /**
     * TODO evalsha
     * SCRIPT FLUSH ：清除所有脚本缓存
     * SCRIPT EXISTS ：根据给定的脚本校验和，检查指定的脚本是否存在于脚本缓存
     * SCRIPT LOAD ：将一个脚本装入脚本缓存，但并不立即运行它
     * SCRIPT KILL ：杀死当前正在运行的脚本
     */

    /**
     * @var string
     *
     * 时间窗口1s
     * key 限流KEY, 保证每秒一个, 不相同
     * limit 限流数
     * 设置2s过期时间,保证1s内计数
     */
    const LUA_SCRIPT = <<<'LUA'
local key = KEYS[1]
local limit = tonumber(ARGV[1]) 
local permits = tonumber(ARGV[2])
local current = tonumber(redis.call("GET", key) or "0")
if current + permits > limit then
    return 0
else
    redis.call("INCRBY", key, permits)
    redis.call("EXPIRE", key, "2")
    return limit - (current + permits)
end
LUA;

    private $redisPool;
    private $keyGen;
    private $rate;

    /**
     * RedisRateLimit constructor.
     * @param object $redisPool TODO TYPE HINT
     * @param callable $keyGen
     * @param $rate
     */
    public function __construct($redisPool, callable $keyGen, $rate)
    {
        $this->rate = $rate;
        $this->redisPool = $redisPool;
        $this->keyGen = $keyGen;
    }

    public function acquire($permits = 1)
    {
        try {
            $redisClient = (yield $this->redisPool->get());
            $keyGen = $this->keyGen;
            $key = $keyGen();
            $limit = $this->rate;
            $left = (yield $redisClient->eval(self::LUA_SCRIPT, 1, $key, $limit, $permits));
            yield $left > 0;
        } catch (\Throwable $ex) {
        } catch (\Exception $ex) { }

        if (isset($ex)) {
            echo $ex;
            yield true;
        }
    }
}