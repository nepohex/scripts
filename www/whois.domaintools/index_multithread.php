<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 01.08.2018
 * Time: 1:47
 * Мультипоточно. Реализовано через блокировку файлов.Пока процесс запущен сбора инфы для домена - другой инстанс скрипта не вмешается в этот сбор.
 * Если файл с результатами для домена с нулевым результатом - будет повторная попытка сбора при запуске другого инстанса.
 */
$time = microtime(true);
include("C:/OpenServer/domains/scripts.loc/www/new/includes/functions.php");
include("C:/OpenServer/domains/scripts.loc/www/new/includes/proxy.php");
include("C:/OpenServer/domains/scripts.loc/www/parser/simpledom/simple_html_dom.php");
$debug_mode = TRUE;
define('EXCLUDE_FAIL_PROXY', TRUE); //если с данной прокси не получили результата (не важно - каптча ли, или просто прокси лежит - ее исключаем из парсинга дальше)
define('SAVE_SCREENS', TRUE); // по возможности сохранять скрины сайта из сервиса
$proxy_timeout = 3; //задержка для получения данных от прокси

echo2("Многопоточная проверкп на whois.domaintools доменов. Можно запускать много дублей скрипта = иммитация мультипоточности.");

#########
$fname_input = 'domains_my_test.txt'; //уникальное имя нужно, от него будем отталкиваться в названиях остальных
$list = file('f:\tmp\proxy_mix_hidemyname.txt', FILE_IGNORE_NEW_LINES);
$urls = file('f:\tmp/' . $fname_input, FILE_IGNORE_NEW_LINES); //список доменов без http и т.п.
$fname_result = './debug/result_domaintools' . date('d-m-Y-H-i') . '_.csv';
$fname_result2 = 'f:\tmp/result_domaintools' . date('d-m-Y-H-i') . '_.csv';
#########

echo2("Подано на проверку " . count($list) . " прокси и " . count($urls) . " доменов");
$list = tmp_synch_bad_proxy($debug['bad_proxies'], "./debug/bad_proxies.txt", $list);
echo2("Синхронизируем список плохих сохраненных прокси, удаляем их перед началом работы. Осталось прокси " . count($list));
foreach ($urls as $url) {
    $fname_domain_res = "./debug/$url";
    $fp = fopen($fname_domain_res, "a+");
    if (file_lock($fp) && fgets($fp) == FALSE) {
        while (!$data) {
            $proxy_id = rand(0, count($list));
            $rnd_proxy = $list[$proxy_id];
            $data = proxy_get_data($rnd_proxy, 'http://whois.domaintools.com/' . $url, $proxy_timeout, FALSE, TRUE);
            //debug
//            $data = file_get_contents("./debug/medwrite.biz.html");
//            $url = 'medwrite.biz';
            //debug
            if ($debug['query_times'] && $debug['query_times'] % 10 == 0) {
                $list = tmp_synch_bad_proxy($debug['bad_proxies'], "./debug/bad_proxies.txt", $list);
                //бывает такое что домен из самого сервиса отдается с задержкой 10+ секунд! В таком случае ставим 10 сек таймаут временный до следующего домена
                $proxy_timeout = 15;
            }
            if (domaintools_check_valid_answer($data, $url)) { // Валидный ответ выглядит вот так ./debug/DEBUG_dont_delete_vallid_answer.txt
                //debug
                echo2($url);
                echo2("Query tries " . $debug['query_times'] . " // Excluded proxies " . $debug['excluded_proxies'] . " // Bad proxies " . $debug['bad_proxy'] . " // Banned proxies " . $debug['proxy_banned']);
                unset ($debug['query_times']);
                //debug
                //Parser первичный HTML, таблицы с данными по домену. 40kb > 3.5kb
                file_put_contents("./debug/$url" . ".html", $data);
                $data = str_get_html($data);
                $res = domaintools_html_scrape1($data, $url);
                $proxy_timeout = 3;
                if ($res[$url]['Screen'] && SAVE_SCREENS) {
                    file_put_contents('./debug/' . basename($res[$url]['Screen']), file_get_contents($res[$url]['Screen']));
                }
            } else {
                tmp_prepare_report($data);
                unset ($data);
            }
        }
        @$i++;
        if ($i % 10 == 0) {
            $list = tmp_synch_bad_proxy($debug['bad_proxies'], "./debug/bad_proxies.txt", $list);
            echo_time_wasted($i, " This thread $i / " . count($urls) . " done // Total Threads Done Of Current Task = " . tmp_get_total_progress($urls) . " // Query tries " . $debug['query_times'] . " // Excluded proxies " . $debug['excluded_proxies'] . " // " . count($list) . " Proxy left");
        }
        file_lock($fp, 1);
        file_put_contents($fname_domain_res, serialize($res));
//        fputs($fp, serialize($res));
        unset ($data, $res);
    }
}
echo_time_wasted();
file_lock($fp, 1);
$dir = scandir("./debug");
$res = array();
foreach ($dir as $file) {
    if (in_array($file, $urls)) {
        $tmp = unserialize(file_get_contents("./debug/" . $file));
        if (is_array($tmp)) {
            $res = array_merge($res, $tmp);
        } else {
            $res[$file]['Domain'] = $file;
        }
    }
}
domaintools_parse_result_put_csv($res, $fname_result, $urls);
copy($fname_result, $fname_result2);

//todo Дописать заись плохих проксей для потока, и синхронизацию с другими потоками
function tmp_synch_bad_proxy($bad_proxy_array, $fp, $current_proxy_list)
{
    if (is_file($fp)) {
        $bad_proxy_saved = file($fp, FILE_IGNORE_NEW_LINES);
        if (@count($bad_proxy_saved) > 0) {
            //Удаляем из текущего листа нерабочие сохраненные в папке прокси.
            $list = array_diff($current_proxy_list, $bad_proxy_saved);
            $list = array_values($list);
        } else {
            $bad_proxy_saved = FALSE;
        }
    }
    if (@count($bad_proxy_array) > 0) {
        if (@is_array($bad_proxy_saved)) {
            $total_bad_proxies = array_merge($bad_proxy_saved, $bad_proxy_array);
            $list = array_diff($current_proxy_list, $total_bad_proxies);
            file_put_contents($fp, implode(PHP_EOL, $total_bad_proxies));
        } else {
            file_put_contents($fp, implode(PHP_EOL, $bad_proxy_array));
        }
    }
    if ($list == FALSE) {
        $list = $current_proxy_list;
    }
    $list = array_values($list);
    return $list;
}

function domaintools_parse_result_put_csv($res, $fname_result, $urls)
{
//Подробная таблица результатов даже для всех зареганых доменов, универсальный код
    if ($res) {
        $fname = $fname_result;
        $fp = fopen($fname, "w");
        $dup = $res; //чтобы не нарушать порядок итемов в массиве и не ВПРить в Excel с остальными данными

        array_multisort(array_map('count', $dup), SORT_DESC, $dup); //получаем массив с самым большим количеством элементов (например о домене много инфы (зареган = 16 элементов) , не зареган и дроп = 5 )
        $header_csv = array_keys(first($dup)); //на основе самого длинного массива делаем шапку и по ней будем ориентироваться дальше
//Добавляем дополнительные колонки которые ниже в глубоком парсинге добавили (если тут менять - то и ниже не забыть!)
        $header_csv[] = 'Registrant';
        $header_csv[] = 'Registrar History'; //Иногда этой колонке может не быть в списке!
        $header_csv[] = 'IP History_years';
        $header_csv[] = 'DROP';
        $header_csv[] = 'Name Servers Changed'; //!!
        $header_csv[] = 'Screen';
        $header_csv = array_unique($header_csv);

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
            if ($cur_item['DROP'] === '') {
                $cur_item['DROP'] = 0;
            }
            fputcsv($fp, array_map('trim', $cur_item), ";");
            unset ($cur_item);
        }
        echo2("Файл записан $fname с " . count($res) . " строками из " . count($urls));
        fclose($fp);
    }
}

function file_lock($fp, $unlock = FALSE)
{
    if ($unlock) {
        flock($fp, LOCK_UN);
        return TRUE;
    }
    if (!flock($fp, LOCK_EX | LOCK_NB, $wouldblock)) {
        if ($wouldblock) {
            // another process holds the lock
            return FALSE;
        } else {
            return FALSE;
            // couldn't lock for another reason, e.g. no such file
        }
    } else {
        return TRUE;
        // lock obtained
    }
}

/** Проверяем получили ли нормальный ответ от сервиса, или же блокировка и т.п.
 * Нормальный ответ содержит тайтл
 * <title>Plasma-Us.com WHOIS, DNS, &amp; Domain Info - DomainTools</title>
 * сразу за тайтлом идет название домена
 * @param $data
 * @param $url
 * @return bool
 */
function domaintools_check_valid_answer($data, $url)
{
    if (@stripos($data, "<title>" . $url)) {
        return TRUE;
    } else {
        return FALSE;
    }
}

function tmp_prepare_report($data)
{
    global $debug, $list, $proxy_id;
    if ($data == FALSE) {
        $debug['bad_proxy'] += 1;
    } else {
        $debug['proxy_banned'] += 1;
    }
    $data = FALSE;
    if (EXCLUDE_FAIL_PROXY) {
        $debug['bad_proxies'][] = $list[$proxy_id];
        unset($list[$proxy_id]);
        $list = array_values($list);
        $debug['excluded_proxies'] += 1;
    }
    $debug['query_times'] += 1;
}

function domaintools_html_scrape1($data, $url)
{
    $res[$url]['Domain'] = $url;
    $stats = $data->find("div[class=stats]");
    $row_labels = $stats[0]->find("td[class=row-label]");
    foreach ($row_labels as $row_label) {
        $res[$url][$row_label->plaintext] = $row_label->next_sibling()->innertext();
//                    echo2($row_label->plaintext . '   ' . $row_label->next_sibling()->innertext()); //debug
    }
    $screen = $data->find("div.thumbnail img");
    if (@count($screen) > 0) {
        $thumb = 'http:' . $screen[0]->src; //http приставка потому что нету изначально и идет в таком виде //thumbnails.domaintools.com/domaintools/2018-08-09T13:27:31.000Z/xewGLw_1IEa-0FjEXKZ4KqkkFno=/medwrite.biz/thumbnail/current/medwrite.jpg
        $res[$url]['Screen'] = $thumb;
    }
    return $res;
}

function tmp_get_total_progress($urls)
{
    $i = 0;
    $dir = scandir("./debug");
    $res = array();
    foreach ($dir as $file) {
        if (in_array($file, $urls)) {
            $i++;
        }
    }
    return $i;
}