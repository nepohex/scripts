<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 07.02.2017
 * Time: 3:08
 * не закончен, только начал
 */
$start = microtime(true);
include("nokogiri.php");
$debug_mode = 1; // Нужно чтобы вывод из функций шел сюда, а не в лог файл.
include('../new/includes/functions.php');

$domain_name = 'ihouzz.ru';
$url = "http://" . $domain_name;
$urls = array(); //Здесь будет результат всех найденных урлов
$max_depth = 3; // Максимальная глубина итераций по сайту
$sleep_time = 1; // Время сна между запросами URL

$result_dir = 'result';
$result_fname = $result_dir . '/texts_' . $domain_name . '.csv';
$result_sitename_fp = $result_dir . "/sitemap_" . $domain_name . ".txt";

//$url = parse_url($url, PHP_URL_HOST);

$visited = get_all_links($url , $max_depth = 3 , $sleep_time = 1);
echo2 ("penis");
function get_all_links($url)
{
    global $urls, $hrefs, $depth;
    if (!$depth) {
        $depth = 1;
    }
    #todo добавить итератор по $depth для записи
    #todo добавить проверку урла был ли он уже в парсе
    $saw = new nokogiri(file_get_contents($url));
    $items_a = $saw->get('a')->toArray();

    if ($items_a) {
        if (!$hrefs) {
            $i = 0;
        } else {
            $i = count($hrefs);
        }
#todo добавить проверку валидности и на дублирование каждого URL в $items_a uri
        foreach ($items_a as $href) {
            if (!$hrefs[$href['href']]) {
                $hrefs[$href['href']]['uri'] = $href['href'];
                $hrefs[$href['href']]['visited'] = 0;
                $hrefs[$href['href']]['depth'] = $depth;
            }

            if ($hrefs[$href['href']]['uri'] == $url) {
                $hrefs['href']['visited'] = 1;
            }
        }
        return $hrefs;
    }
}
