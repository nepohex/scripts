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
$result_fname = $result_dir . '/texts_' . $domain_name . '.csv';
$result_sitename_fp = $result_dir . "/sitemap_" . $domain_name . ".txt";

$urls = file($result_sitename_fp, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$fp = fopen($result_fname, 'w');

$counter_articles = 0;
$counter_images = 0;

echo2("Стартуем обход ".count($urls)." URL для сайта $domain_name");
foreach ($urls as $article) {
    $counter_articles++;
    $i = 0;
//    $article = 'http://pophaircuts.com/gorgeous-long-hairstyle-designs';
    $html = file_get_html($article);
    sleep(2);
    $div = $html->find('div[class=theiaPostSlider_slides] div', 0);
    while ($item = $div->children($i++)) {
        $items_tagname[] = $item->tag;
        $items_plaintext[] = $item->plaintext;
        $items_inner[] = $item->innertext();
    }
    $items_plaintext = preg_replace($ascii_preg,'',$items_plaintext);
    $items_inner = preg_replace($ascii_preg,'',$items_inner);
    $tmp = array_count_values($items_tagname);

    //В некоторых статьях вместо h2 используется h3.
    $header_tag = 'h2';
    if ($tmp[$header_tag] == false) {
        $header_tag = 'h3';
    }
    if ($tmp[$header_tag] >= 10 && count($tmp['figure']) == count($tmp[$header_tag])) {
        $i = 0;
        $z = 0;
        //Получаем ID элементов родительского DIV которые будем парсить. У нас связка h2 -> figure(img) -> p (абзац текста).
        foreach ($items_tagname as $item_id => $tagname) {
            if ($items_tagname[$i] == $header_tag && $items_tagname[$i + 1] == 'figure' && $items_tagname[$i + 2] == 'p') {
                $parse_ids[$z]['h'] = $item_id;
                $parse_ids[$z]['img'] = $item_id + 1;
                $parse_ids[$z]['p'] = $item_id + 2;
                $z++;
            }
            $i++;
        }
        $counter_images += count($parse_ids);
        $i = 0;
        // Записываем данные о каждой картинке в массив.
        foreach ($parse_ids as $id) {
            $images[$i]['url'] = $article;
            $images[$i]['h3'] = html_entity_decode($items_plaintext[$id['h']], ENT_QUOTES, 'UTF-8');
            $images[$i]['text'] = html_entity_decode($items_plaintext[$id['p']], ENT_QUOTES, 'UTF-8');
            $tmpimg = str_get_html($items_inner[$id['img']]);
            $images[$i]['img_url'] = $tmpimg->find('img',0)->src;
            $images[$i]['img_alt'] = $tmpimg->find('img',0)->alt;
            $images[$i]['img_source'] = $tmpimg->find('img',0)->href;
            $i++;
        }
        echo2("#$counter_articles нашли всего $counter_images URL: $article");
        foreach ($images as $image) {
            fputcsv($fp, $image, ';');
        }
        unset ($parse_ids, $items_plaintext, $items_tagname, $items_inner, $html, $div, $tmpimg,$images);
    } else {
        echo2("#$counter_articles Не равное количество картинок и заголовков, или меньше 10. Пропускаем $article");
    }
    unset ($html, $div);
    if ($counter_articles % 50 == 0) {
        echo_time_wasted($counter_articles);
    }
}