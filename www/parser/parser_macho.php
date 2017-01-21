<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 21.01.2017
 * Time: 18:18
 * Парсер сетки сайтов.
 * Парсим сначала карту сайта, составляем ее, пример карты сайта с постами и указаниями их картинок сохранен в папке со скриптом
 * sitemap_example_parsing_2_lvl.xml
 * Посмотреть вживую можно на сайтах http://machohairstyles.com/sitemap.xml
 * Сделать все четко не получилось, есть косяки со съездом картинок из-за неровностей формата!
 * http://machohairstyles.com/
 */
$start = microtime(true);
include("nokogiri.php");
$debug_mode = 1; // Нужно чтобы вывод из функций шел сюда, а не в лог файл.
include('../new/includes/functions.php');

$domain_name = 'machohairstyles.com';
// Плохие символы кавычки одинарные, двойные.
$bad_symbols = array('â', 'â', 'â', 'вЂ', 'вЂ™', 'â');
$bad_symbols2 = array('â', 'ГўВЂВ”');;
$result_dir = 'result';
$result_fname = $result_dir . '/texts_' . $domain_name . '.csv';
$result_sitename_fp = $result_dir . "/sitemap_" . $domain_name . ".txt";

//SITEMAP делаем.
if (is_file($result_sitename_fp) == false) {
    get_sitemap_items_multilvl($domain_name);
}

function get_sitemap_items_multilvl($domain_name, $result_dir = 'result')
{
// Получение списка статей для парсинга, sitemap 1ого уровня где есть Image сразу прописанные.
    if (!is_dir($result_dir)) {
        mkdir($result_dir, 0755, true);
    }
    $sitemap = file_get_contents('http://' . $domain_name . '/sitemap.xml');
    $saw = new nokogiri($sitemap);
    $sitemap_items = $saw->get('loc')->toArray();
    $sitemap_items2 = array();

    $i = 0;
    foreach ($sitemap_items as $sitemap_item) {
        $saw = new nokogiri(file_get_contents($sitemap_item['#text']));
        $sitemap_items2 = array_merge($sitemap_items2, $saw->get('loc')->toArray());
        sleep(2);
        $i++;
//        if ($i % 10 == 0) echo_time_wasted($i);
    }
    foreach ($sitemap_items2 as $item) {
        $urls[] = $item['#text'];
    }

    $fp = fopen("result/sitemap_" . $domain_name . ".txt", 'w');
    foreach ($urls as $item) {
        fwrite($fp, $item . PHP_EOL);
    }
    fclose($fp);

    file_put_contents("result/sitemap_srlz_" . $domain_name . ".txt", serialize($urls));
    // Конец Sitemap
}

function check_arr_implode($array)
{
    /**
     * Если массив, переводит его в строку.
     */
    if (is_array($array)) {
        $str = implode(' ', $array);
        return $str;
    } else {
        return $array;
    }
}

$urls = file($result_sitename_fp, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$fp = fopen($result_fname, 'w');
$i = 0;
$counter_articles = 0;

foreach ($urls as $article) {
    $saw = new nokogiri(file_get_contents($article));
    sleep(2);
    $counter_articles++;

    //slider content
    $items_all = $saw->get('#all_content')->toArray();
    $items_p = $saw->get('#all_content p')->toArray();
    if (count($items_p) == 0) {
        $items_p = $saw->get('#all_content #text')->toArray();
    }
    $items_h3 = $saw->get('#all_content h2')->toArray();
    $items_img = $saw->get('#all_content img')->toArray();
    $debug['url'] = $article;
    $debug['$items_all'] = count($items_all);
    $debug['$items_p'] = count($items_p);
    $debug['$items_h3'] = count($items_h3);
    $debug['$items_img'] = count($items_img);
    $p = 1; //метка абзаца для слайдера 2.
//    если нет слайдера, а обычная статья:
    if (in_array('0', $debug)) {
        unset($items_all, $items_p, $items_h3, $items_img, $debug);
        $items_all = $saw->get('div.theiaPostSlider_slides div')->toArray();
        $items_p = $saw->get('div.theiaPostSlider_slides div p')->toArray();
        if (count($items_p) == 0) {
            $items_p = $saw->get('div.theiaPostSlider_slides div #text')->toArray();
        }
        $items_h3 = $saw->get('div.theiaPostSlider_slides div h2')->toArray();
        $items_img = $saw->get('div.theiaPostSlider_slides div img')->toArray();
        $debug['url'] = $article;
        $debug['$items_all'] = count($items_all);
        $debug['$items_p'] = count($items_p);
        $debug['$items_h3'] = count($items_h3);
        $debug['$items_img'] = count($items_img);
        $p = 2; // Метка абзаца, для статьи 2 начальная.
        //если ни слайдер ни статья, нам нужны только статьи с картинками и h3
    }
    if (in_array('0', $debug)) {
        unset($items_all, $items_p, $items_h3, $items_img, $debug);
        echo2("Не получилось получить итемы, что-то пошло не так. Урл $article");
    }
    if (is_array($items_all) && count($items_h3) >= 10 && count($items_img) >= 10 && count($items_h3) >= 10) {
        $z = 0;
//        $p = 2; //Стартовый 2 чтобы зацепить P 2й картинки.
        foreach ($items_h3 as $item) {
            $images[$i]['url'] = $article;
            $images[$i]['h3'] = $item['#text'];
            while ($items_p[$p]['#text'] == false) {
                if ($items_p[$p]['a']) {
                    $images[$i]['img_source'] = $items_p[$p]['a'][0]['href'];
                }
                if ($p >= count($items_p)) {
                    echo2("Не получилось получить итемы, что-то пошло не так. Урл $article");
                    break;
                }
                $p++;
            }
            if ($p >= count($items_p)) {
                echo2("Выходим из парсинга $article , здесь подписи не через <p> пошли.");
                $not_write = 1;
                break;
            }
            if ($images[$i]['img_source'] == false) {
                $images[$i]['img_source'] = ' ';
            }
            $items_p[$p]['#text'] = check_arr_implode($items_p[$p]['#text']);
            $images[$i]['text'] = str_replace($bad_symbols2, '-', str_replace($bad_symbols, '\'', $items_p[$p]['#text']));
            $images[$i]['img_url'] = $items_img[$z]['src'];
            $images[$i]['img_alt'] = $items_img[$z]['alt'];
            $i++;
            $z++;
            $p++;
        }
        $counter_images += $z;
//        echo2 ("На странице $article получили ");
//        print_r2 ($debug);
        if ($not_write == false) {
            foreach ($images as $image) {
                fputcsv($fp, $image, ';');
            }
        }
        unset($images, $items_all, $items_p, $items_h3, $items_img, $not_write);
        echo2("Нашли $counter_images картинки, идем по строке $counter_articles, статья $article");
    }
}