<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 04.03.2017
 * Time: 23:19
 */
include('../new/includes/functions.php');
$debug_mode = 1;
$db_pwd = '';
$db_usr = 'root';
mysqli_connect2("pinterest");

$ftp_server = 'ftp.godaddy.com';
$ftp_user_name = 'auctions';
$ftp_user_pass = '';
$server_file = 'closeouts.xml.zip'; //Название архива на FTP Godaddy
$extracted_file = 'closeouts.xml'; //Лежит в архиве
$work_dir = 'tmp/';
$pin_db = 'godaddy_closeout';

if (is_file($work_dir . $server_file) == false) {
    // установка соединения
    $conn_id = ftp_connect($ftp_server);
    // вход с именем пользователя и паролем
    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
    ftp_pasv($conn_id, true);
    //$buff = ftp_rawlist ($conn_id,'/');
    //print_r($buff);
    $buff = ftp_mdtm($conn_id, $server_file);
    // попытка скачать $server_file и сохранить в $local_file
    if (ftp_get($conn_id, $work_dir . $server_file, $server_file, FTP_BINARY)) {
        echo_time_wasted(null, "Скачали файл с Godaddy $server_file , размером " . convert(filesize($work_dir . $server_file)));
        // закрытие соединения и локального файла
        ftp_close($conn_id);
    } else {
        echo2("Не удалось скачать файл с FTP");
        exit();
    }
} else {
    echo2("Уже есть файл с FTP Godaddy в папке скачанный");
}

//Распаковка
if (is_file($work_dir . $extracted_file) == false) {
    $zip = new ZipArchive;
    if ($zip->open($work_dir . $server_file) === TRUE) {
        $zip->extractTo($work_dir);
        $zip->close();
        echo2("Распаковали архив в $work_dir");
    } else {
        echo2("Не получилось распаковать файл $server_file");
    }
} else {
    echo2("Уже есть распакованный файл в папке");
}

//Запись в db
if (is_file($work_dir . $extracted_file)) {
    $fp = fopen($work_dir . $extracted_file, "r");
    echo2("Начинаем загружать строки в базу");
    while ($line = fgets($fp)) {
        $counter_all++;
        if ($tmp = preg_match('/<item><title>(.){8,25}<\/title>/i', $line, $matches)) {
            $counter_length++;
            $domain = strtolower(substr($matches[0], 13, -8));
            if (preg_match('/^[-a-z0-9]+\.biz|com|net|org|info|us|xyz|online|pro|tv|black|red$/', strtolower($domain))) {
                $counter_valid++;
                $query = "INSERT INTO `pinterest`.`$pin_db` (`id`, `domain`, `status`) VALUES (NULL, '$domain', '0')";
                if (dbquery($query, null, true, null, 'shutup') == 1) {
                    $counter_uploaded++;
                }
            }
        }
        if ($counter_all % 10000 == 0) {
            echo2("Идем по строке $counter_all, загрузили $counter_uploaded");
        }
    }
    echo_time_wasted(null, "ВСЕГО ДОМЕНОВ В ФАЙЛЕ / ДЛИНА (до 25 сим) / VALID ДОМЕННАЯ ЗОНА / ЗАГРУЗИЛИ В БАЗУ");
    echo2("$counter_all / $counter_length / $counter_valid / $counter_uploaded");

}