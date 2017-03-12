<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 07.03.2017
 * Time: 14:51
 */

$com = new Com('WScript.shell');
$scripts = array('', 'pinterest_db.php', 'pinterest_deep_db.php', 'godaddy_db.php', 'godaddy_deep_db.php', 'dead_search.php', 'dead_db.php', 'dead_deep.php');
switch ($argv[2]) {
    case 1:
        $go = $scripts[1];
        break;
    case 2:
        $go = $scripts[2];
        break;
    case 3:
        $go = $scripts[3];
        break;
    case 4:
        $go = $scripts[4];
        break;
    case 5:
        $go = $scripts[5];
        break;
    case 6:
        $go = $scripts[6];
        break;
    case 7:
        $go = $scripts[7];
        break;
    default:
        $go = '';
}
if ($argv[3]) {
    $sleep = $argv[3];
} else {
    $sleep = 7;
}
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