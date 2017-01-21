<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 21.01.2017
 * Time: 2:10
 * Парсер сетки сайтов.
 * Парсим сначала карту сайта, составляем ее, пример карты сайта с постами и указаниями их картинок сохранен в папке со скриптом
 * sitemap_example_parsing_1_lvl.xml
 * Посмотреть вживую можно на сайтах hairstylezz.com/post-sitemap.xml
 *
http://tophairstyles.net/
http://machohairstyles.com/
 */

$start = microtime(true);
include ("nokogiri.php");
$debug_mode = 1; // Нужно чтобы вывод из функций шел сюда, а не в лог файл.
include('../new/includes/functions.php');

$domain_name = 'tophairstyles.net';
// Плохие символы кавычки одинарные, двойные.
$bad_symbols = array('â', 'â','â','вЂ','вЂ™','â');
$bad_symbols2 = array('â','ГўВЂВ”'); ;
$result_dir = 'result';
$result_fname = $result_dir.'/texts_'.$domain_name.'.csv';
$result_sitename_fp = $result_dir."/sitemap_" . $domain_name . ".txt";

//SITEMAP делаем.
if (is_file($result_sitename_fp) == false){
    get_sitemap_items($domain_name);
}

function get_sitemap_items($domain_name, $result_dir = 'result')
{
// Получение списка статей для парсинга, sitemap 1ого уровня где есть Image сразу прописанные.
    if (!is_dir($result_dir))
    {
        mkdir($result_dir, 0755, true);
    }
    $sitemap = file_get_contents('http://' . $domain_name . '/post-sitemap.xml');
    $saw = new nokogiri($sitemap);
    $sitemap_items = $saw->get('loc')->toArray();

    foreach ($sitemap_items as $sitemap_item) {
        if (strstr($sitemap_item['#text'], '/uploads/')) {
            $images[] = $sitemap_item['#text'];
        } else {
            $urls[] = $sitemap_item['#text'];
        }
    }

    $fp = fopen("result/sitemap_" . $domain_name . ".txt", 'w');
    foreach ($urls as $item) {
        fwrite($fp, $item . PHP_EOL);
    }
    fclose($fp);

    $fp = fopen("result/images_" . $domain_name . ".txt", 'w');
    foreach ($images as $item) {
        fwrite($fp, $item . PHP_EOL);
    }
    fclose($fp);

    file_put_contents("result/sitemap_srlz_" . $domain_name . ".txt", serialize($urls));
    file_put_contents("result/images_srlz_" . $domain_name . ".txt", serialize($images));
    // Конец Sitemap
}

$urls = file($result_sitename_fp,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$fp = fopen($result_fname,'w');
$i = 0;
$counter_articles = 0;
foreach ($urls as $article) {
    $article = 'http://tophairstyles.net/best-chocolate-brown-hair/';
    $saw = new nokogiri(file_get_contents($article));
    sleep(2);
    $counter_articles++;
    //slider content
    $items_all = $saw->get('#content_box')->toArray();
    $items_p = $saw->get('#content_box p')->toArray();
    $items_h3 = $saw->get('#content_box h2')->toArray();
    $items_img = $saw->get('#content_box img')->toArray();
    $debug['url'] = $article;
    $debug['$items_all'] = count($items_all);
    $debug['$items_p'] = count($items_p);
    $debug['$items_h3'] = count($items_h3);
    $debug['$items_img'] = count($items_img);
    //если нет слайдера, а обычная статья:
//    if (in_array('0',$debug) ) {
//        unset($items_all,$items_p,$items_h3,$items_img,$debug);
//        $items_all = $saw->get('div.post_content')->toArray();
//        $items_p = $saw->get('div.post_content p')->toArray();
//        $items_h3 = $saw->get('div.post_content h3')->toArray();
//        $items_img = $saw->get('div.post_content div.wp-caption img')->toArray();
//        $debug['url'] = $article;
//        $debug['$items_all'] = count($items_all);
//        $debug['$items_p'] = count($items_p);
//        $debug['$items_h3'] = count($items_h3);
//        $debug['$items_img'] = count($items_img);
//        //если ни слайдер ни статья, нам нужны только статьи с картинками и h3
//    }
    if (in_array('0',$debug)) {
        unset($items_all,$items_p,$items_h3,$items_img,$debug);
        echo2 ("Не получилось получить итемы, что-то пошло не так. Урл $article");
    }
    if (is_array($items_all) && count($items_h3) > 10 && count($items_img) > 10 && count($items_h3) > 10) {
        $z = 0;
        $p = 2; //Стартовый 2 чтобы зацепить P 2й картинки.
        foreach ($items_h3 as $item) {
            if ($z == count($items_h3) - 1) { // Последний h3 у них с подпиской.
                break;
            }
            $images[$i]['url'] = $article;
            $images[$i]['h3'] = $item['#text'];
            if (is_array($items_p[$z]['#text'])){ //Иногда бывает массивом текст, когда есть ссылки внутри текста. Пофиг на текст ссылки, просто скрепляем текст.
                foreach ($items_p[$z]['#text'] as $p_text) {
                    $tmp .= $p_text;
                }
                unset($items_p[$z]['#text']);
                $items_p[$z]['#text'] = $tmp;
                unset ($tmp);
            }
            while ($items_p[$p]['#text'] == false) {
                if ($items_p[$p]['a']) {
                    $images[$i]['img_source'] = $items_p[$p]['a']['href'];
                }
                $p++;
                if ($p == count ($items_p)) {
                    echo2 ("Не получилось получить итемы, что-то пошло не так. Урл $article");
                    break;
                }
            }
            if ($p == count ($items_p)) {
                echo2 ("Выходим из парсинга $article , здесь подписи не через <p> пошли.");
                $write = 0;
                break;
            }
            if ($images[$i]['img_source'] == false) {
                $images[$i]['img_source'] = ' ';
            }
            $images[$i]['text'] = str_replace($bad_symbols2,'-',str_replace($bad_symbols,'\'',$items_p[$p]['#text']));
            $images[$i]['img_url'] = $items_img[$z]['src'];
            $images[$i]['img_alt'] = $items_img[$z]['alt'];
            $i++;
            $z++;
            $p++;
        }
        $counter_images += $z;
//        echo2 ("На странице $article получили ");
//        print_r2 ($debug);
        if ($write) {
            foreach ($images as $image) {
                fputcsv($fp, $image, ';');
            }
            unset($images,$write);
            echo2("Нашли $counter_images картинки, идем по строке $counter_articles, статья $article");
        }
    }
}