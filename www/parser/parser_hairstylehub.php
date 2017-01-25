<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 22.01.2017
 * Time: 20:53
 * Пссле завершения нужны правки все вручную.
 */
$start = microtime(true);
include("nokogiri.php");
$debug_mode = 1; // Нужно чтобы вывод из функций шел сюда, а не в лог файл.
include('../new/includes/functions.php');

$domain_name = 'hairstylehub.com';
// Плохие символы кавычки одинарные, двойные.
$bad_symbols = array('â', 'â', 'â', 'вЂ', 'вЂ™', 'â','вЂ™');
$bad_symbols2 = array('â', 'ГўВЂВ”');;
$result_dir = 'result';
$result_fname = $result_dir . '/texts_' . $domain_name . '.csv';
$result_sitename_fp = $result_dir . "/sitemap_" . $domain_name . ".txt";

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
    $items_p = $saw->get('.td-sml-description p #text')->toArray();
    if (count($items_p) == 0) {
        $items_p = $saw->get('.td-sml-description p')->toArray();
    }
    $items_h3 = $saw->get('.td-sml-current-item-title')->toArray();
    $items_img = $saw->get('.td-sml-description img')->toArray();
    $debug['url'] = $article;
    $debug['$items_p'] = count($items_p);
    $debug['$items_h3'] = count($items_h3);
    $debug['$items_img'] = count($items_img);
    $p = 1; //метка абзаца для слайдера 2.
    if (in_array('0', $debug)) {
        unset($items_all, $items_p, $items_h3, $items_img, $debug);
        echo2("Не получилось получить итемы, что-то пошло не так. Урл $article");
    }
    if (count($items_h3) >= 10 && count($items_img) >= 10 && count($items_h3) >= 10) {
        $z = 0;
        foreach ($items_h3 as $item) {
            $images[$i]['url'] = $article;
            $images[$i]['h3'] = $item['#text'];
            while ($items_p[$p]['#text'] == false) {
                if ($items_p[$p]['span']['#text']) {
                    break;
                }
                if ($p >= count($items_p)) {
                    echo2("Не получается найти P тексты. Урл $article");
                    break;
                }
                $p++;
            }
            if ($p >= count($items_p)) {
                echo2("Выходим из парсинга $article , здесь подписи не через <p> пошли.");
                $not_write = 1;
                break;
            }
            if ($items_p[$p]['#text'] == false) {
                $items_p[$p]['#text'] = $items_p[$p]['span']['#text'];
            }
            $images[$i]['text'] = str_replace($bad_symbols2, '-', str_replace($bad_symbols, '\'', $items_p[$p]['#text']));
            $images[$i]['img_url'] = $items_img[$z]['src'];
            $images[$i]['img_alt'] = ' ';
            $images[$i]['img_source'] = ' ';

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