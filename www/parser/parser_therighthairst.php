<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 06.01.2017
 * Time: 21:52
 * парсим костыльно TheRighthairstyles.com с помощью Nokogiri. Результат 8860 картинок с описаниями, 2.4 млн символов контента.
 * Результат надо вручную обрабатывать от левых символов в Excel.
 */
$start = microtime(true);
include("nokogiri.php");
$double_log = 1;
include('../new/includes/functions.php');

$domain_name = 'therighthairstyles.com';
$bad_symbols = array('â', 'â', 'â', 'вЂ', 'вЂ™', 'â');
$bad_symbols2 = array('â', 'ГўВЂВ”');;
$result_dir = 'result';
$result_fname = $result_dir . '/texts_2' . $domain_name . '.csv';
$result_sitename_fp = $result_dir . "/sitemap_2" . $domain_name . ".txt";
$fp_log = $result_dir. '/log_'.$domain_name.'.txt';
$start = microtime(true);

if (is_file($result_sitename_fp) == false) {
// Получение списка статей для парсинга, sitemap , многоуровневая SITEMAP
    $sitemap = file_get_contents('http://therighthairstyles.com/sitemap.xml');
    $saw = new nokogiri($sitemap);
    $sitemap_items = $saw->get('loc')->toArray();
    $sitemap_items2 = array();
    $i = 0;
    foreach ($sitemap_items as $sitemap_item) {
        $saw = new nokogiri(file_get_contents($sitemap_item['#text']));
        $sitemap_items2 = array_merge($sitemap_items2, $saw->get('loc')->toArray());
        sleep(2);
        $i++;
        if ($i % 10 == 0) echo_time_wasted($i);
    }
    foreach ($sitemap_items2 as $item) {
        $sitemap_fin[] = $item['#text'];
    }

    $fp = fopen($result_sitename_fp, 'w');
    foreach ($sitemap_fin as $item) {
        fwrite($fp, $item . PHP_EOL);
    }
    fclose($fp);

    //file_put_contents("sitemap_srlz_therighthair_com.txt", serialize($sitemap_fin));
// Конец Sitemap
}


//Debug file test
//$html = file_get_contents('http://therighthairstyles.com/30-best-hairstyles-and-haircuts-for-women-over-60-to-suit-any-taste/');
//$html = file_get_contents("hair1.html");

// Плохие символы кавычки одинарные, двойные.
$bad_symbols = array('â', 'â', 'â', 'вЂ', 'вЂ™', 'â');
$bad_symbols2 = array('â', 'ГўВЂВ”');;
//$z = 'Itâs a pity to cut beautiful curly hair. If itâs healthy and features sufficient thickness, why not to retain the length? Susan Sarandon shows us a good example of medium curly haircut for women over 60. Such whimsical waves can be achieved with mousse or any other curl-enhancer, applied to damp locks.';
//$p = 'New tendencies in hair styles for 2016 guide us towards livelier and more textured looks, like Diane Keatonâs âenergeticâ bob hairstyle. It appears spontaneous and present day, but fresh and appropriate for Dianeâs age and appearance.';
//$p = str_replace($bad_symbols,'\'',$p);
//â¦ = ...

$sitemap = explode(PHP_EOL, file_get_contents($result_sitename_fp));
$i = 0;
$counter_articles = 0;
$fp = fopen($result_fname, 'a');
foreach ($sitemap as $article) {
    $saw = new nokogiri(file_get_contents($article));
    sleep(2);
    $counter_articles++;
    //slider content
    $items_all = $saw->get('div.post_cont')->toArray();
    $items_p = $saw->get('div.post_cont p')->toArray();
    $items_h3 = $saw->get('div.post_cont h3')->toArray();
    $items_img = $saw->get('div.post_cont div.wp-caption img')->toArray();
    $debug['url'] = $article;
    $debug['$items_all'] = count($items_all);
    $debug['$items_p'] = count($items_p);
    $debug['$items_h3'] = count($items_h3);
    $debug['$items_img'] = count($items_img);
    //если нет слайдера, а обычная статья:
    if (in_array('0', $debug)) {
        unset($items_all, $items_p, $items_h3, $items_img, $debug);
        $items_all = $saw->get('div.post_content')->toArray();
        $items_p = $saw->get('div.post_content p')->toArray();
        $items_h3 = $saw->get('div.post_content h3')->toArray();
        $items_img = $saw->get('div.post_content div.wp-caption img')->toArray();
        $debug['url'] = $article;
        $debug['$items_all'] = count($items_all);
        $debug['$items_p'] = count($items_p);
        $debug['$items_h3'] = count($items_h3);
        $debug['$items_img'] = count($items_img);
        //если ни слайдер ни статья, нам нужны только статьи с картинками и h3
    }
    if (in_array('0', $debug)) {
        unset($items_all, $items_p, $items_h3, $items_img, $debug);
        echo2 ("Не получилось получить итемы, что-то пошло не так. Урл $article");
    }

    // Удаляем блок рекламы
    if (is_array($items_all)) {
        $p = 0;
        foreach ($items_all[0]['div'] as $item) {
            if (in_array('insert-post-ads', $item)) {
                unset ($items_all[0]['div'][$p]);
            }
            $p++;
        }
        $p > 0 ? $items_all[0]['div'] = array_values($items_all[0]['div']) : $nothing = 'nothing';
        //
    }
    if (is_array($items_all) && (count($items_all[0]['h3']) == count($items_all[0]['div']))) {
        $z = 0;
        foreach ($items_all[0]['h3'] as $item) {
            // Кривой костыль, только 1 ссылка если в тексте, и только если ссылка.
            if (is_array($items_all[0]['p'][$z]['#text'])) {
                $items_all[0]['p'][$z]['#text'] = $items_all[0]['p'][$z]['#text'][0].$items_all[0]['p'][$z]['a'][0]['#text'].$items_all[0]['p'][$z]['#text'][1];
            }
            $images[$i]['url'] = $article;
            $images[$i]['h3'] = $item['#text'];
            $images[$i]['text'] = str_replace($bad_symbols2, '-', str_replace($bad_symbols, '\'', $items_all[0]['p'][$z]['#text']));
            $images[$i]['img_url'] = $items_all[0]['div'][$z]['img'][0]['src'];
            $images[$i]['img_alt'] = $items_all[0]['div'][$z]['img'][0]['alt'];
            $images[$i]['img_source'] = $items_all[0]['div'][$z]['p'][0]['a']['href'];
            $i++;
            $z++;
        }
        echo2 ("На странице $article получили ");
        echo2 ('<pre>', print_r($debug, 1), '</pre>');
        foreach ($images as $image) {
            fputcsv($fp, $image, ';');
        }
        unset($images);
    }
}
echo "fixed!";