<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 27.02.2018
 * Time: 1:47
 * TEST PROXY
 */
$list = file('f:\tmp\work_prox.txt', FILE_IGNORE_NEW_LINES);

$time = microtime(true);
foreach ($list as $proxy) {
    $tmp_time = microtime(true);
    if (proxy_test($proxy, 2)) {
        echo $proxy . ' ' . intval(microtime(true) - $tmp_time) . ' ms   ' . 'WORK' . PHP_EOL;
    } else {
        echo $proxy . ' ' . intval(microtime(true) - $tmp_time) . ' ms   ' . 'FAIL' . PHP_EOL;
    }
}