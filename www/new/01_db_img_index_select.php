<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 27.11.2016
 * Time: 18:08
 * Выбираем из базы данных по ключу все KeyCollector данные и все данные по картинкам, один из самых ресурсоемких. Занимает 15 минут на 87к строк картинок т.к. тут регулярки и т.п.
 */
include "multiconf.php";
mysqli_connect2($db_name_img);

next_script (0,1);
echo2("Будем выгружать из базы " . $db_name . " данные по KeyCollector и адреса на картинки");

$pattern = '/-.?[0-9]\w+/i';

// Блок для выгрузки из таблицы KK_KEYS, Если файла с выгрузкой по ключу с таким же количеством строк как в базе сейчас нету - выгружаем. Если есть - проходим мимо.
$query = "SELECT COUNT(*) FROM `semrush_keys` WHERE `key` LIKE '%" . $keyword . "%';";
$db_results = dbquery($query);
$res_kk = $selects_dir . '/' . $keyword . "_kk.csv";
if (!(file_exists($res_kk))) {
    echo2("Ключей в таблице KK_KEYS по фразе " . $keyword . " всего " . $db_results . ". Файла с выгрузкой обнаружено не было, начинаем выгружать из базы одним запросом! Результат будет сохранен в файл " . $res_kk);
    $query = "SELECT `key`,`adwords` FROM `semrush_keys` WHERE `key` LIKE '%" . $keyword . "%';";
    $tmp = dbquery($query);
    array_to_csv($res_kk, $tmp, false, "Успешно Записали файл с выгрузкой из KK");
    unset($tmp);
    echo_time_wasted();
} else {
    echo2("Файл KK_KEYS под ключ $keyword ранее был создан, используем его.");
}
$res_kk2 = $selects_dir . '/' . $keyword . "_images.csv";

if (!(file_exists($res_kk2))) {
    echo2("Файла для импорта картинок под ключ $keyword еще не создано, идем создавать.");
// Создаем временную таблицу если результатов в таблице kk_keys относительно общего количества строк в базе мало (например, 20 тысяч из 330). Это должно ускорить процесс, временная таблица хранится в оперативной памяти
    if ($db_results < 100000) {
        $query = "CREATE TEMPORARY TABLE `" . $res_kk . "` AS (SELECT `key`,`adwords` FROM `semrush_keys` WHERE `key` LIKE '%" . $keyword . "%');";
        $sqlres = mysqli_query($link, $query);
        $table_to_select = $res_kk;
    } else {
        $table_to_select = 'semrush_keys';
    }

    $query = "SELECT COUNT(*) FROM `images` WHERE `filename` LIKE '%" . $keyword . "%';";
    $db_results = dbquery($query);

    $counter_start_limit = 0;

    if ($db_results < 10000) {
        $go_small_site = 1;
        echo2("Результатов по ключу " . $keyword . " всего " . $db_results . ". Начинаем выгружать из временной таблицы которую создали " . $res_kk . "! Результат будет сохранен в файл " . $res_kk2 . " разом.");
    } else {
        $counter_limit_queries = 1000;
        echo2("Результатов по ключу " . $keyword . " всего " . $db_results . ". Начинаем выгружать из временной таблицы которую создали " . $res_kk . "! Результат будет сохранен в файл " . $res_kk2 . " пачками по " . $counter_limit_queries . " строк после обработки");
    }
    if ($go_small_site == false) {
        while ($counter_start_limit < ($db_results - $counter_limit_queries)) {
            $query = "SELECT * from `images` where `filename` LIKE '%" . $keyword . "%' LIMIT " . $counter_start_limit . ", $counter_limit_queries ;";
            $images = dbquery($query);
            $i = 0;
            foreach ($images as $image) {
                $images[$i]['title'] = clean_files_name ($image['filename']);
                $query = "SELECT `adwords` FROM `" . $table_to_select . "` WHERE `key` = '" . $images[$i]['title'] . "' LIMIT 1;";
                $tmp = dbquery($query);
                if ($tmp !== false) {
                    $images[$i]['adwords'] = $tmp;
                } else {
                    $images[$i]['adwords'] = 0;
                }
                $i++;
            }
            array_to_csv($res_kk2, $images);
            $counter_start_limit += $counter_limit_queries;
            unset($images, $image, $tmp);
        }
    } else {
        $query = "SELECT * from `images` where `filename` LIKE '%" . $keyword . "%';";
        $images = dbquery($query);
        $i = 0;
        foreach ($images as $image) {
            //Говнокостыль для извлечения вот такого Cool-Hairstyle-For-Ladies-Over-40.jpg , цифер 40
            if (stripos($images[$i]['filename'], 'over')) {
                $z = explode("-", $image['filename']);
                $k = array_search(strtolower('over'), array_map('strtolower', $z));
                preg_match('/\d{2}/', $z[$k + 1], $matchez);
            }
            //
            $images[$i]['title'] = preg_replace($pattern, "", $image['filename']); // Выражение помогает избавиться от 54bf176a17b60 и В любом случае убивает год
//    $images[$i]['title'] = preg_replace($year_pattern,$year_to_replace,$images[$i]['title']);
            $images[$i]['title'] = trim(preg_replace('/\d/', "", $images[$i]['title'])); //добиваем все оставшиеся цифры
            $images[$i]['title'] = strtolower(trim(str_replace($replace_symbols, ' ', $images[$i]['title'])));
            //Говнокостыль для извлечения вот такого Cool-Hairstyle-For-Ladies-Over-40.jpg , цифер 40
            if ($matchez) {
                $images[$i]['title'] .= ' ' . $matchez[0];
                unset($matchez);
            }
            //
            $query = "SELECT `adwords` FROM `" . $table_to_select . "` WHERE `key` = '" . $images[$i]['title'] . "' LIMIT 1;";
            $tmp = dbquery($query);
            if ($tmp !== false) {
                $images[$i]['adwords'] = $tmp;
            } else {
                $images[$i]['adwords'] = 0;
            }
            $i++;
        }
        array_to_csv($res_kk2, $images);
        $counter_start_limit += $counter_limit_queries;
        unset($images, $image, $tmp);
    }
    echo2 ("Файл импорта с адресами картинок создали.");
} else {
    echo2("Файл импорта с адресами картинок из таблицы semrush_keys для ключа $keyword уже создан, используем его!");
}
next_script ();