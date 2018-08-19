<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 007.07.08.2018
 * Time: 16:30
 * Мини скрипт для удобного вывода Topical Trust Flow из Majestic, полученного от ExpiredDomains отчета.
 */
$time = microtime(true);
include("C:/OpenServer/domains/scripts.loc/www/new/includes/functions.php");
$debug_mode = TRUE;
$input = "F:/tmp/majestic_ttf_tpl.csv";
$output = "F:/tmp/majestic_ttf_result.csv";

$input_column = 'TTF'; //Колонка из которой будем забирать топики для разбора.

//Метрики которые будем считать и выводить
$metrics = array(
    'Single Theme', //Ставим только если родительская Тематика Единственная. До слеша или же не имеет слеша, например  ( 5 - Recreation (16.93%) ИЛИ 16 - Arts/Radio (98.11%), 10 - Arts/Music (1.80%) )
    'Main Theme', //Тематика с максимальным TTF. Например здесь будет Recreation.  6 - News/Newspapers (68.84%), 5 - Recreation (16.93%), 5 - Recreation/Roads and Highways (13.19%), 3 - Sports/Soccer (0.49%), 3 - News/Magazines and E-zines (0.34%)
    'Parent Themes Count', //Количество РАЗНЫХ родительских тематик. ПРИМЕР 1 (результат 4) ( 6 - News/Newspapers (68.84%), 5 - Recreation (16.93%), 5 - Recreation/Roads and Highways (13.19%), 3 - Sports/Soccer (0.49%), 3 - News/Magazines and E-zines (0.34%) ) ПРИМЕР 2 (результат 1)  ( 16 - Arts/Radio (98.11%), 10 - Arts/Music (1.80%) )
    'Total TTF', //Суммарный TTF всех тематик которые выведены
    'Total Main Theme TTF', //Суммарный TTF главной темы
    'Main Theme TTF %', //Разница Главной темы TTF К общему TTF
    'Max TTF % for single theme' //Сколько приходится % по версии Majestic на один топик. Не по TTF числу, а по %.
);

$i = 0;
if (($handle = fopen($input, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        //Находим ключ колонки которую будем парсить, которая содержит TTF
        if ($ttf_column == FALSE) {
            if (array_search($input_column, $data) !== FALSE) {
                $ttf_column = array_search($input_column, $data);
            }
            $header = array_merge($data, $metrics); //Заголовок будущего CSV
            $rows[] = $header;
            continue;
        }
        $array = tmp_parse_TTF($data[$ttf_column]);
        if ($array) {
            $main_theme_name = array_shift(array_keys($array, max($array)));
            if (count($array) == 1) {
                $single_theme = $main_theme_name;
            } else {
                $single_theme = '';
            }
            $parent_themes_count = count($array);
            $total_ttf = array_sum($array);
            $main_theme_ttf_number = max($array);
            $main_theme_ttf_percent = str_replace('.', ',', round($main_theme_ttf_number / $total_ttf, 2));
            $tmp = tmp_parse_TTF_percent($data[$ttf_column]);
            $max_ttf_percent_single_theme = array_shift($tmp);

            $res[] = $single_theme;
            $res[] = $main_theme_name;
            $res[] = $parent_themes_count;
            $res[] = $total_ttf;
            $res[] = $main_theme_ttf_number;
            $res[] = $main_theme_ttf_percent;
            $res[] = $max_ttf_percent_single_theme;
            $row = array_merge($data, $res);

            $rows[] = $row;
        } else {
            $rows[] = $data;
        }
        unset ($res);
    }
    fclose($handle);
}

//Запись результата в CSV
$fp = fopen($output, "w+");
foreach ($rows as $row) {
    fputcsv($fp, $row, ";");
}

echo2("FIN!");

function tmp_parse_TTF($ttf_string)
{
    if ($ttf_string) {
        $ttf = explode(',', $ttf_string);
        if (is_array($ttf)) {
            foreach ($ttf as $topic) {
                preg_match('/(\d+) - (\w+).*?(\d+)/i', $topic, $matches);
                if (is_array($matches)) {
                    $res[$matches['2']] += $matches['1'];
                }
            }
        } else {
            return FALSE;
        }
    } else {
        return FALSE;
    }
    return $res;
}

function tmp_parse_TTF_percent($ttf_string)
{
    if ($ttf_string) {
        $ttf = explode(',', $ttf_string);
        if (is_array($ttf)) {
            foreach ($ttf as $topic) {
                preg_match('/(\d+) - (\w+).*?(\d+)/i', $topic, $matches);
                if (is_array($matches)) {
                    $res[$matches['2']] += $matches['3'];
                }
            }
        } else {
            return FALSE;
        }
    } else {
        return FALSE;
    }
    return $res;
}