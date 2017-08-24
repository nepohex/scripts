<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 23.01.2017
 * Time: 18:31
 */
include('simpledom/simple_html_dom.php');
$start = microtime(true);
$debug_mode = 1; // Нужно чтобы вывод из функций шел сюда, а не в лог файл.
include('../new/includes/functions.php');

$domain_name = 'pophaircuts.com';

$ascii_preg = '/&#[1-9]{4};/'; //Вылазят символы типа &#8217; из ASCII, не знаю как избавиться - по регулярке все удаляю.
$result_dir = 'result';
$result_fname = $result_dir . '/texts_2' . $domain_name . '.csv';
$result_sitename_fp = $result_dir . "/sitemap_" . $domain_name . ".txt";

$urls = file($result_sitename_fp, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$fp = fopen($result_fname, 'w');

$counter_articles = 0;
$counter_images = 0;

echo2("Стартуем обход " . count($urls) . " URL для сайта $domain_name");

function put_data_csv($images, $fp = null)
{
    if ($fp == false) {
        global $fp;
    }
    foreach ($images as $image) {
        fputcsv($fp, $image, ';');
    }
}

foreach ($urls as $article) {
    $counter_articles++;
    $i = 0;
//    $article = 'http://pophaircuts.com/14-short-hairstyles-women';
    $html = file_get_html($article);
    sleep(2);
    $div = $html->find('div[class=theiaPostSlider_slides] div', 0);
    while ($item = $div->children($i++)) {
        $items_tagname[] = $item->tag;
        $items_plaintext[] = $item->plaintext;
        $items_inner[] = $item->innertext();
    }
    $items_plaintext = preg_replace($ascii_preg, '', $items_plaintext);
    $items_inner = preg_replace($ascii_preg, '', $items_inner);
    $tmp = array_count_values($items_tagname);

    // Пробуем отловить вот такие статьи где картинка в другом теге. http://pophaircuts.com/casual-easy-updos-bar-refaeli-hair-styles
    if (array_key_exists('figure', $tmp) == false) {
        $div = $html->find('figure[class=image]', 0);
        $images[0]['url'] = $article;
        $header = $html->find('h1', 0);
        $images[0]['h3'] = $header->plaintext;
        $images[0]['text'] = html_entity_decode($items_plaintext[0], ENT_QUOTES, 'UTF-8');
        if ($div) {
            $images[0]['img_url'] = $div->find('img', 0)->src;
            $images[0]['img_alt'] = $div->find('img', 0)->alt;
            $images[0]['img_source'] = '';
            put_data_csv($images);
        } else if ($div = $html->find('img[class=size-full]', 0)) {
            $images[0]['img_url'] = $div->src;
            $images[0]['img_alt'] = $div->alt;
            $images[0]['img_source'] = '';
            put_data_csv($images);
        }
        echo2("#$counter_articles нашли 1 картинку. URL $article");
        $counter_images++;
        $done = 1;
    }
    //В некоторых статьях вместо h2 используется h3.
    $header_tag = 'h2';
    if ($tmp[$header_tag] == false) {
        $header_tag = 'h3';
    }
    if ($tmp[$header_tag] == false) {
        $header_tag = 'h1';
    }

    //Основной цикл поиска данных где идут поочередно картинки-текст-заголовок.
    if ($tmp[$header_tag] >= 1) {
        $i = 0;
        $z = 0;
        //Получаем ID элементов родительского DIV которые будем парсить. У нас связка h2 -> figure(img) -> p (абзац текста).
        foreach ($items_tagname as $item_id => $tagname) {
            //Если идет header (h2/h3) -> img -> p
            if ($items_tagname[$i] == $header_tag && $items_tagname[$i + 1] == 'figure' && $items_tagname[$i + 2] == 'p') {
                if (strlen($items_plaintext[$item_id + 2]) > 60) {
                    $parse_ids[$z]['h'] = $item_id;
                    $parse_ids[$z]['img'] = $item_id + 1;
                    $parse_ids[$z]['p'] = $item_id + 2;
                    $z++;
                }
            }
            //Если идет header (h2/h3) -> p -> img
            if ($items_tagname[$i] == $header_tag && $items_tagname[$i + 1] == 'p' && $items_tagname[$i + 2] == 'figure') {
                if (strlen($items_plaintext[$item_id + 1]) > 60) {
                    $parse_ids[$z]['h'] = $item_id;
                    $parse_ids[$z]['img'] = $item_id + 2;
                    $parse_ids[$z]['p'] = $item_id + 1;
                    $z++;
                }
            }
            $i++;
        }
        $counter_images += count($parse_ids);
        $i = 0;

        // Записываем данные о каждой картинке в массив.
        if (!($parse_ids)) {
            echo2("#$counter_articles Не нашлось блоков header > p > img | header > img > p $article");
        } else {
            foreach ($parse_ids as $id) {
                $images[$i]['url'] = $article;
                $images[$i]['h3'] = html_entity_decode($items_plaintext[$id['h']], ENT_QUOTES, 'UTF-8');
                $images[$i]['text'] = html_entity_decode($items_plaintext[$id['p']], ENT_QUOTES, 'UTF-8');
                $tmpimg = str_get_html($items_inner[$id['img']]);
                $images[$i]['img_url'] = $tmpimg->find('img', 0)->src;
                $images[$i]['img_alt'] = $tmpimg->find('img', 0)->alt;
                $images[$i]['img_source'] = $tmpimg->find('img', 0)->href;
                $i++;
            }
            echo2("#$counter_articles нашли всего $counter_images картинок. URL: $article");
            foreach ($images as $image) {
                fputcsv($fp, $image, ';');
            }
            $done = 1;
        }
        unset ($parse_ids, $items_plaintext, $items_tagname, $items_inner, $html, $div, $tmpimg, $images, $parse_ids);
    } else if ($done == 0) {
        //Отлавливаем статьи вроде этой http://pophaircuts.com/14-short-hairstyles-women где много фото и текст не чередуется.
        $div = $html->find('h1', 0);
        $images[0]['url'] = $article;
        $images[0]['h3'] = $div->plaintext;
        $images[0]['text'] = html_entity_decode($items_plaintext[0], ENT_QUOTES, 'UTF-8');
        $images[0]['img_url'] = $html->find('img', 0)->src;
        $images[0]['img_alt'] = $div->plaintext;
        $images[0]['img_source'] = '';
        put_data_csv($images);
        echo2("#$counter_articles нашли 1 картинку. URL $article");
        $counter_images++;
        $done = 1;
    } else if ($done == 0) {
        echo2("#$counter_articles не найдены заголовки h2/h3 в количестве 10 и более штук $article");
    }
    unset ($html, $div, $items_plaintext, $items_inner, $items_tagname, $done);
    if ($counter_articles % 50 == 0) {
        echo_time_wasted($counter_articles);
    }
}