<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 07.03.2017
 * Time: 14:51
 */

$com = new Com('WScript.shell');
$scripts = array(
    0 => '',
    1 => 'pinterest_db.php',
    2 => 'pinterest_deep_db.php',
    3 => 'godaddy_db.php',
    4 => 'godaddy_deep_db.php',
    5 => 'dead_search.php',
    6 => 'dead_db.php',
    7 => 'dead_deep.php',
    8 => 'dead_houzz.php',
    9 => 'dead_houzz_db.php',
    10 => 'dead_houzz_deep.php');
if ($argv[3]) {
    $sleep = $argv[3];
} else {
    $sleep = 7;
}
$go = $scripts[$argv[2]];
echo "STARTING $go - $argv[1] TIMES , SLEEP $sleep" . PHP_EOL;
echo date('r') . PHP_EOL;
flush();
for ($i = 0; $i < $argv[1]; $i++) {
    sleep($sleep);
    $com->run('php C:\OpenServer\domains\scripts.loc\www\pinterest\\' . $go . ' 2>&1', 0, false); //2ой параметр положительный чтобы консоль видимой была
}
echo "DONE $argv[1] threads of $go!" . PHP_EOL;
echo date('r');
echo print_r($scripts);
//Такой вариант не сработал
//for ($i = 0 ; $i < 30 ; $i++) {
//    $out = array();
//    exec('C:\OpenServer\modules\php\PHP-5.6\php-cgi.exe -c C:\OpenServer\modules\php\PHP-5.6\php.ini -q -f C:\OpenServer\domains\scripts.loc\www\pinterest\test.php 2>&1',$out);
//    print_r($out);
//    sleep(1);
//}