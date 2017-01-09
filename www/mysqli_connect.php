<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 16.11.2016
 * Time: 4:44
 */

$link = mysqli_init();
// Задается в CONFIG , Важно его подключить до MYSQL CONNECT
//$db_usr = 'root';
//$db_name = 'mh_parse2';
//$db_pwd = '';

if (!$link) {
    die('mysqli_init завершилась провалом');
}

//Непонятное гавно из-за которого 2 часа провел в поисках проблемы, не обновляет базу и все тут!
//if (!mysqli_options($link, MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT = 0')) {
//    die('Установка MYSQLI_INIT_COMMAND завершилась провалом');
//}

if (!mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 5)) {
    die('Установка MYSQLI_OPT_CONNECT_TIMEOUT завершилась провалом');
}

if (!mysqli_real_connect($link, 'localhost', $db_usr, $db_pwd, $db_name)) {
    die('Ошибка подключения (' . mysqli_connect_errno() . ') '
        . mysqli_connect_error());
}

//echo "Соединение с базой ".$db_name." есть... " . mysqli_get_host_info($link) . " Едем дальше! <br>";
?>