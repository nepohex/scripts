<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 21.05.2017
 * Time: 1:12
 * Выгружаем логи с сервера
 * Составляем сводную по топовым картинкам которые запрашивались с сервера по убыванию
 * Определяем в байтах по логам размер картинки (200 код)
 * Размеры картинок закидываем в TXT
 * По размеру в байтах выгружаем из DB `image_size` , определяем с какого сайта картинка была успешной
 * Сопоставляем с первоначально созданным файлом на момент импорта какие картинки в % от какого сайта были успешными
 */
$start = microtime(true);
$db_usr = 'root';
$db_host = 'localhost';
$db_pwd = '';
$db_name = 'image_index';
include "includes/functions.php";
$debug_mode = 1;

$bytes = file('f:\tmp\cody_list_bytes.txt',FILE_IGNORE_NEW_LINES);
mysqli_connect2();
