<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 07.05.2018
 * Time: 13:04
 * Цель:
 * Сократить место хранения картинок на сервере, выяснилось что по тематике Anatomy из 18гб только 3гб уникальных, в среднем на 1 картинку приходится по 10 названий (комбинаций ключей). Нет смысла хранить 10 раз одну и ту же картинку.
 *
 * Механика:
 * Картинки определяем сторонним софтом какие дубли (Visual Similarity Duplicate Image Finder)
 * Экспортируем в CSV, формат 8 колонок (описаны ниже в $list)
 * Берем первую картинку из группы, пишем ее в новое место обитания картинки, все дублирующие ее - записываем в базу только ключи (old_name), остальные параметры не пишем.
 * Загружаем в базу инфу по основной картинке и ее дублям
 * Картинку переносим в новое место обитания, разбивка по папкам (тематики).
 *
 * Этапы добавления новых картинок (новыш спаршенных сайтов):
 * 1. Удаление через del_crops.php (удаление 150x150 в названиях файлов + картинки меньше заданного размера).
 * 2. СОФТ: Сравнивается с папкой bad для рубрики, которая сначала вручную создается путем сортировки-просмотра картинок (можно не делать,если рубрика не сортируется). Фотки bad похожие - удаляются прямо в программе.
 * 3. СОФТ: Сверка с основной папкой картинок, создается файл .csv если есть уже похожие файлы, подается файл в этот скрипт (mode 2)
 * 4. Доливка остальных картинок: просто подается на вход папка, доливаются все остальные в базу.
 */

include "../includes/functions.php";
$fp_log = __DIR__ . '/debug_data/log.txt';
prepare_dir(dirname($fp_log));
$debug_mode = 1;
$double_log = 1;
$db_name = 'image_index';
$t_name = 'image_doubles';

$arr = csv_to_array2('f:\tmp\anatomymix4.csv', ',', null, true); //Закомментировать если хотим импортнуть папку ($dir).
if (count($arr) < 2) {
    echo2("Что-то не так с CSV! Выходим!");
    exit();
}
$dir = 'f:\Dumps\downloaded sites\bodypart.science\wp-content\uploads\2018\my\\'; //указывать не обязательно если подаем СSV
$theme = 2; // Указать тематику картинок условную, обязательно перед стартом скрипта!
$new_path = 'f:\Dumps\google_images\anatomy\\'; //Путь новый к картинкам, привязка к тематике, менять обязательно!

//Определяем что делать.
// На вход можно подать 2 варианта CSV:
// 1 - папка на импорт в базу сверенная только с самой собой.
// 2 - папка на импорт, сверенная с уже имеющимися картинками в папке для указанной тематики.
// 3 - закомментить CSV и подать только папку = импорт всей папки (нужно когда остался в ней только уник).

//В зависимости от этого выполняем 1 часть скрипта (добавление новой тематики, или обычный импорт картинок)
//либо же выполняем дозаливку, определив по названию любой из подаваемых картинок в CSV что она уже есть в базе как родительская.
if (is_array($arr)) {
    foreach ($arr as $item) {
        $full_path = $item[1];
        $old_name = basename($full_path);
        if (dbquery("SELECT * FROM `$db_name`.`$t_name` WHERE `new_name` = '$old_name';")) {
            $mode = 2;
            break;
        }
    }
    if ($mode !== 2) {
        $mode = 1;
    }
} else if (is_dir($dir)) {
    echo2("На вход не получили CSV файл, получили папку $dir - начинаем импортировать ее");
    $mode = 3;
}

//Загрузка файла CSV без сравнения с базой текущей и текущей папкой, рабочий вариант если тематика впервые добавляется или картинки в папке уникальные по отношению к текущей базе.
if ($mode == 1) {
    $i = 0;
    foreach ($arr as $item) {
        $i++;
        list($group, $full_path, $size, $date, $width, $height, $similarity, $checked) = $item;
        $new_name = md5($full_path . microtime()) . strrchr($full_path, '.');
        $old_name = basename($full_path);
        if (is_file($full_path)) {
            if (@$group_trigger == FALSE || $group_trigger !== $group) {
                $group_trigger = $group;
                $query = "INSERT INTO `$db_name`.`$t_name` VALUES ('','$old_name','$new_name',$size,$width,$height,'',$theme);";
                if (!is_file($new_path . $new_name)) {
                    copy($full_path, $new_path . $new_name);
                    @$counter_fsize += $size;
                    @$f++;
                } else {
                    @$counter_fsize_double += $size;
                    @$z++;
                }
                if (dbquery($query, 0, 1) == 1) {
                    $parent_id = dbquery("SELECT MAX(`id`) FROM `$t_name`;");
                } else {
                    echo2("Ошибка при добавлении записи в таблицу! Картинка не загрузилась в базу " . print_r($item, true) . " ");
                }
            } else {
                //Если картинка имеет ту же группу (идентичные картинки = одна группа), то не создаем ей новое имя, а записываем потенциальные ключи только.
                $query = "INSERT INTO `$db_name`.`$t_name` VALUES ('','$old_name','','','','',$parent_id,$theme);";
                @$counter_economy += $size;
                unlink($full_path);
                dbquery($query);
            }
        } else {
            @$b++;
        }
    }
    echo2("Всего прошли циклов по CSV $i, загрузили картинок в базу $f весом " . convert($counter_fsize));
    echo2("Уже было картинок в папке $new_path , дубли не добавляли = $z , весом " . convert($counter_fsize_double));
    echo2("Сэкономили места (вес дублируемых картинок) " . convert($counter_economy));
    echo2("Не существовало картинок $b / $i поэтому никаких действий не предпринималось.");
}

//Дозагрузка файла сравнений с текущей папкой указанной тематики, и базой по этой тематике. Удаление дублей из папки импорта.
if ($mode == 2) {
    echo2("Стартуем Mode = 2 : Сравнение с эталогом картинок, замена если эталон меньше размером, добавление новых ключей для имеющихуся и дублируемых фоток. На вход CSV строк " . count($arr));
    echo2 ("Шаг 1 - Поиск в базе parent_id для групп картинок");
    $i = 0;
    foreach ($arr as $item) {
        $i++;
        list($group, $full_path, $size, $date, $width, $height, $similarity, $checked) = $item;
        $old_name = basename($full_path);
        if (is_file($full_path)) {
            $group_arr[$group][] = $full_path;
            //Ищем родительский ID
            if (($parent_id = dbquery("SELECT `id` FROM `$db_name`.`$t_name` WHERE `new_name` = '$old_name';")) !== FALSE) {
                $group_arr[$group]['parent_id'] = $parent_id;
                if (($key = array_search($full_path, $group_arr[$group])) !== false) {
                    unset($group_arr[$group][$key]);
                    @$p++;
                }
            }
        } else {
            @$b++;
        }
        $i % 1000 == 0 ? echo_time_wasted($i) : '';
    }
    if (is_array($group_arr)) {
        echo2 ("Шаг 2 - начинаем проверку размеров эталонов, доливку в базу");
        $i = 0;
        foreach ($group_arr as $item) {
            if (key_exists('parent_id', $item)) {
                $parent_id = $item['parent_id'];
                foreach ($item as $key => $full_path) {
                    $i++;
                    $i % 1000 == 0 ? echo_time_wasted($i) : '';
                    $old_name = basename($full_path);
                    if ($key !== 'parent_id') {
                        //Если новая картинка больше уже имеющейся в базе, то перезаписываем старую на новую и в базе и в папке, прописываем новые данные.
                        ###
                        $size = filesize($full_path);
                        $db_imgsize = dbquery("SELECT `new_name`,`size` FROM `$db_name`.`$t_name` WHERE `id` = $parent_id", 1);
                        if ($size > $db_imgsize[0][1]) {
                            $new_size += $size;
                            $old_size += $db_imgsize[0][1];
                            $imgdata = is_image($full_path);
                            $width = $imgdata[0];
                            $height = $imgdata[1];
                            dbquery("UPDATE `$db_name`.`$t_name` SET `size` = '$size' , `width` = '$width', `height` = '$height' WHERE `id` = '$parent_id';");
                            copy($full_path, $new_path . $db_imgsize[0][0]);
                            @$r++;
                        }
                        ###
                        $query = "INSERT INTO `$db_name`.`$t_name` VALUES ('','$old_name','','','','',$parent_id,$theme);";
                        @$f++;
                        dbquery($query);
                        unlink($full_path);
                    }
                }
            }
        }
    }
    echo2("Всего прошли циклов по CSV $i, из них : Догрузили в базу и удалили из исходной папки картинок $f , Родительских картинок (групп) было $p");
    echo2("Не существовало картинок $b / $i по которым никаких действий не предпринималось.");
    echo2("Заменили в базе и папке старых картинок на новые $r , их вес изменился " . convert($old_size) . " -> " . convert($new_size));
}

//Импорт всей папки (нужно когда остался в ней только уник).
if ($mode == 3) {
    $i = 0;
    $arr = scandir($dir);
    foreach ($arr as $item) {
        if (($imgdata = is_image($dir . $item)) !== FALSE) {
            $i++;
            $old_name = $item;
            $new_name = md5($dir . $item . microtime()) . strrchr($item, '.');
            $size = filesize($dir . $item);
            $width = $imgdata[0];
            $height = $imgdata[1];
            $query = "INSERT INTO `$db_name`.`$t_name` VALUES ('','$old_name','$new_name',$size,$width,$height,'',$theme);";
            dbquery($query);
            @$f++;
            if (!is_file($new_path . $new_name)) {
                copy($dir . $item, $new_path . $new_name);
                unlink($dir . $item);
                @$counter_fsize += $size;
            } else {
                $z++;
                @$counter_fsize_double += $size;
                unlink($dir . $item);
            }
        }
    }
    echo2("Всего картинок было в папке прошли циклов по CSV $i, загрузили картинок $f в базу и папку весом " . convert($counter_fsize));
    echo2("Уже было картинок в папке $new_path , дубли не добавляли = $z , весом " . convert($counter_fsize_double));
}