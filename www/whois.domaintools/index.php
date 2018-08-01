<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 01.08.2018
 * Time: 1:47
 * Single Thread
 */
$time = microtime(true);
include("C:/OpenServer/domains/scripts.loc/www/new/includes/functions.php");
include("C:/OpenServer/domains/scripts.loc/www/new/includes/proxy.php");
include("C:/OpenServer/domains/scripts.loc/www/parser/simpledom/simple_html_dom.php");
$debug_mode = TRUE;
define('EXCLUDE_FAIL_PROXY', TRUE); //если с данной прокси не получили результата (не важно - каптча ли, или просто прокси лежит - ее исключаем из парсинга дальше)

echo2("Файл предназначен для однопоточной проверки на whois.domaintools доменов, получения данных через прокси в 1 поток. На вход обязательно указать уникальное название файла. Запуск только в 1 поток!");
$fname_input = 'domains_my_test.txt'; //уникальное имя нужно, от него будем отталкиваться в названиях остальных
$list = file('f:\tmp\proxy_rus_hidemyname.txt', FILE_IGNORE_NEW_LINES);
$urls = file('f:\tmp/' . $fname_input, FILE_IGNORE_NEW_LINES); //список доменов без http и т.п.
echo2("Подано на проверку " . count($list) . " прокси и " . count($urls) . " доменов");

if (is_file("./debug/tmp_" . $fname_input)) {
    $res = unserialize(file_get_contents("./debug/tmp_" . $fname_input));
    $done_domains = array_column($res, "Domain");
    $i = count($res);
    echo2("Нашли уже проверенных $i доменов, продолжаем с этого же места");
    domaintools_parse_result_put_csv($res, $fname_input, $urls);
    echo2("Создаем CSV из уже проверенных (на случай спешки чтобы можно было с данными уже работать, продолжаем в это время обработку остального");
} else {
    $i = 0;
    echo2("Проверенных доменов из данного списка не нашли, начинаем файл прогонять сначала");
}
foreach ($urls as $url) {
    if (@!in_array($url, $done_domains)) {
        while (!$data) {
            $proxy_id = rand(0, count($list));
            $rnd_proxy = $list[$proxy_id];
            $data = proxy_get_data($rnd_proxy, 'http://whois.domaintools.com/' . $url, 3, FALSE, TRUE);
            if (!stripos($data, "<title>" . $url)) {
                if ($data == FALSE) {
                    $debug['bad_proxy'] += 1;
                } else {
                    $debug['proxy_banned'] += 1;
                }
                $data = FALSE;
                if (EXCLUDE_FAIL_PROXY) {
                    unset($list[$proxy_id]);
                    $list = array_values($list);
                    $debug['excluded_proxies'] += 1;
                }
                $debug['query_times'] += 1;
            } else {
                //Parser
                $res[$i]['Domain'] = $url;
                $done_domains[] = $url;
                //debug
                echo2($url);
                echo2("Query tries " . $debug['query_times'] . " / Excluded proxies Total " . $debug['excluded_proxies']);
                unset ($debug['query_times']);
                //debug
                $data = str_get_html($data);
                $stats = $data->find("div[class=stats]");
                $row_labels = $stats[0]->find("td[class=row-label]");
                foreach ($row_labels as $row_label) {
                    $res[$i][$row_label->plaintext] = $row_label->next_sibling()->innertext();
//                    echo2($row_label->plaintext . '   ' . $row_label->next_sibling()->innertext()); //debug
                }
            }
        }
        @$i++; // не переносить! иначе не будет работать multidim
        if ($i % 10 == 0) {
            echo_time_wasted($i, " Total $i / " . count($urls) . " done . Query tries " . $debug['query_times'] . " / Excluded proxies Total " . $debug['excluded_proxies'] . " / " . count($list) . " Proxy left");
        }
        file_put_contents("./debug/tmp_" . $fname_input, serialize($res));
        unset ($data);
    }
}
echo_time_wasted();
domaintools_parse_result_put_csv($res, $fname_input, $urls);
echo2("Закончили прогон, записали в файл .debug/result_$fname_input.csv");

function domaintools_parse_result_put_csv($res, $fname_input, $urls)
{
//Подробная таблица результатов даже для всех зареганых доменов, универсальный код
    if ($res) {
        $fname = './debug/result_' . $fname_input . ".csv";
        $fp = fopen($fname, "a+");
        $dup = $res; //чтобы не нарушать порядок итемов в массиве и не ВПРить в Excel с остальными данными

        array_multisort(array_map('count', $dup), SORT_DESC, $dup); //получаем массив с самым большим количеством элементов (например о домене много инфы (зареган = 16 элементов) , не зареган и дроп = 5 )
        $header_csv = array_keys(first($dup)); //на основе самого длинного массива делаем шапку и по ней будем ориентироваться дальше
//Добавляем дополнительные колонки которые ниже в глубоком парсинге добавили (если тут менять - то и ниже не забыть!)
        $header_csv[] = 'IP History_years';
        $header_csv[] = 'DROP';
        $header_csv[] = 'Name Servers Changed'; //!!

        fputcsv($fp, $header_csv, ";"); //пишем шапку
        foreach ($res as $row) {
            $cur_keys = array_keys($row); //все ключи текущего элемента
            //код чтобы не сбивались офсеты для элементов где 5 строк о домене получили, где 16, чтобы дата была по правильным колонкам
            foreach ($header_csv as $k => $v) {
                if (in_array($v, $cur_keys)) {
                    $cur_item[$v] = $row[$v]; //если есть этот ключ (строка из исходного массива) - пишем его и значение
                } else {
                    $cur_item[$v] = ''; //если нету, то просто создаем элемент пустой
                }
            }
            foreach ($cur_item as $k => $item) {
                $cur_item[$k] = str_replace(array(";", "\t", ':'), '', $item);
            }
            //Распарсим детально колонки, уберем шлак.
            foreach ($cur_item as $k => $v) {
                if ($k == 'Registrar') {
                    $tmp = strpos($v, " < ");
                    $cur_item[$k] = substr($v, 0, $tmp);
                }
                if ($k == 'Tech Contact') {
                    $tmp = strpos($v, "\n");
                    $tmp = substr($v, 0, $tmp);
                    $cur_item[$k] = strip_tags($tmp);
                }
                if ($k == 'IP History') {
                    //3 changes        on 3 unique IP addresses over 2 years
                    preg_match_all('/\d+/', $v, $tmp);
                    if (@count($tmp[0]) > 0) {
                        $cur_item['IP History'] = $tmp[0][0];
                        $cur_item['IP History_years'] = $tmp[0][2];
                    }
                }
                if ($k == 'Registrar History') {
                    //1 registrar
                    //5 registrars                     with 4 drops
                    preg_match_all('/\d+/', $v, $tmp);
                    if (@count($tmp[0]) > 0) {
                        $cur_item['Registrar History'] = $tmp[0][0];
                        if ($tmp[0][1]) {
                            $cur_item['DROP'] = $tmp[0][1];
                        } else {
                            $cur_item['DROP'] = 0;
                        }
                    }
                }
                if ($k == 'Hosting History') {
                    // 6 changes        on 4 unique name servers        over 2 years
                    preg_match_all('/\d+/', $v, $tmp);
                    if (@count($tmp[0]) > 0) {
                        $cur_item['Hosting History'] = $tmp[0][0];
                        $cur_item['Name Servers Changed'] = $tmp[0][1];
                    }
                }
            }
            fputcsv($fp, array_map('trim', $cur_item), ";");
            unset ($cur_item);
        }
        echo2("Файл записан ./debug/result_$fname с " . count($res) . " строками из " . count($urls));
    }
}