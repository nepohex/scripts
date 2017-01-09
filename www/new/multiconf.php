<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 10.12.2016
 * Time: 2:34
 * Функция находит в каталоге все файлы с названиями Config (которые копии конфигов, вариации заданий на генерацию сайтов), выдает для текущего выполнения скрипта конфиг
 * Когда сайт сгенерен, на последнем шаге, ставим статус fin, и ищем есть ли еще задания на выполнение.
 */
$current_config = get_config();
include $current_config;

function get_config($fin = null)
{
    $status_fp = 'status.txt';
    global $start_script;
    // Передача тикера $fin означает окончание линейки скриптов, запись в файла статуса
    if ($fin) {
        $config_files = unserialize(file_get_contents($status_fp));
        $i = 0;
        foreach ($config_files as $item) {
            if ($item['status'] == 'current') {
                $config_files[$i]['status'] = 'fin';
                file_put_contents($status_fp, serialize($config_files));
                break;
            }
            $i++;
        }
        $i = 0;
        foreach ($config_files as $item) {
            if ($item['status'] == 'new') {
                return header('Location: ' . $start_script);
            }
            $i++;
        }
        echo2("Закончили генерацию всех сайтов, переходить больше некуда!");
    } else {
        // Сканим каталоги на предмет конфигов, все файлы *config* идут в ход
        $z = scandir(getcwd());
        $i = 0;
        if (!file_exists($status_fp)) {
            foreach ($z as $item) {
                if (stripos($item, 'config') !== false) {
                    $config_files[$i]['confname'] = $item;
                    $config_files[$i]['status'] = 'new';
                    $i++;
                }
            }
            file_put_contents($status_fp, serialize($config_files));
        } else if (file_exists($status_fp)) {
            $config_files = unserialize(file_get_contents($status_fp));
            foreach ($z as $item) {
                if (stripos($item, 'config') !== false) {
                    $tmpconffp[] = $item;
                }
            }
            if (count($tmpconffp) > count($config_files)) {
                foreach ($config_files as $config_file) {
                    $config_items[] = $config_file['confname'];
                }
                $new_confs = array_diff($tmpconffp, $config_items);
                if ($new_confs) {
                    $i = count($config_files);
                    foreach ($new_confs as $new_conf) {
                        $config_files[$i]['confname'] = $new_conf;
                        $config_files[$i]['status'] = 'new';
                        $i++;
                    }
                }
            }
        }
        //После получения всех конфигов return переменной какой конфиг юзать, если нет current берем new
        foreach ($config_files as $item) {
            if ($item['status'] == 'current') {
                $current_config = $item['confname'];
                return $current_config;
            }
        }
        $i = 0;
        foreach ($config_files as $item) {
            if ($item['status'] == 'new') {
                $current_config = $item['confname'];
                $config_files[$i]['status'] = 'current';
                file_put_contents($status_fp, serialize($config_files));
                return $current_config;
                break;
            }
            $i++;
        }
        //Если нет ни current, ни NEW, значит надо выводить статус окончания работы скрипта, общее время выполнения, количество созданных сайтов
    }
}

?>