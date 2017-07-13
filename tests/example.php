<?php

namespace ZanPHP\RateLimit;


require __DIR__ . "/../../contracts/src/RateLimit/RateLimiter.php";
require __DIR__ . "/../src/APCuTokenBucket.php";
require __DIR__ . "/../src/APCuLeakyBucket.php";


function test_apcuTokenBucket()
{
    $capacity = 2000;
    $perSecond = 2000;

    $tokenBucket = new APCuTokenBucket($capacity, $perSecond);

    $pids = [];
    register_shutdown_function(function() use(&$pids) {
        foreach ($pids as $pid) {
            posix_kill($pid, SIGKILL);
        }
    });

    for ($i = 0; $i < 4; $i++) {
        $pid = pcntl_fork();
        if ($pid < 0) {
            exit("fork fail");
        }

        if ($pid === 0) {
            while (true) {
                usleep(300 * 1000);
                $permits = rand(100, 300);
                echo getmypid(), ": ", $tokenBucket->acquire($permits) ? "ok" : "ko", "\n";
            }
            exit;
        }

        $pids = [$pid];
    }

    $tokenBucket->start();
}


function test_apcuLeakyBucket()
{
    $capacity = 2000;
    $perSecond = 2000;

    $tokenBucket = new APCuLeakyBucket($capacity, $perSecond);

    $pids = [];
    register_shutdown_function(function() use(&$pids) {
        foreach ($pids as $pid) {
            posix_kill($pid, SIGKILL);
        }
    });

    for ($i = 0; $i < 4; $i++) {
        $pid = pcntl_fork();
        if ($pid < 0) {
            exit("fork fail");
        }

        if ($pid === 0) {
            while (true) {
                usleep(300 * 1000);
                $permits = rand(100, 300);
                echo getmypid(), ": ", $tokenBucket->acquire($permits) ? "ok" : "ko", "\n";
            }
            exit;
        }

        $pids = [$pid];
    }

    $tokenBucket->start();
}


