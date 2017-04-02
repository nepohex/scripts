<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 07.03.2017
 * Time: 14:48
 */
//echo $argc;
//$com = new Com('WScript.shell');
//$com->run('php C:\OpenServer\domains\scripts.loc\www\pinterest\pinterest_db.php 2>&1', 0, false); //2ой параметр положительный чтобы консоль видимой была
//print_r($argv);
//file_put_contents(microtime(true).".txt",microtime(true));
//echo ("проверка кодировки");
//flush();
//sleep(120);
//exec("php C:\\OpenServer\\domains\\scripts.loc\\www\\pinterest\\exec.php 1 8", $output, $status); var_dump($output, $status);
//$name = microtime(true);
//file_put_contents(microtime(true),microtime(true));
//if (is_file($name)) {
//    echo "yahoo!";
//}
//$exec = exec('php C:\OpenServer\domains\scripts.loc\www\pinterest\exec.php 10 3 2>&1');
$exec = exec("php C:\\OpenServer\\domains\\scripts.loc\\www\\pinterest\\exec.php 5 3 7 2>&1");
echo "penis";
//$com = new Com('WScript.shell');
//$com->run("php C:\\OpenServer\\domains\\scripts.loc\\www\\pinterest\\exec.php 1 3 2>&1", 0, false);