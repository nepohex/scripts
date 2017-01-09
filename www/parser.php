<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 06.01.2017
 * Time: 21:52
 */
$start = microtime(true);
include ("nokogiri.php");

function convert($memory_usage)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($memory_usage/pow(1024,($i=floor(log($memory_usage,1024)))),2).' '.$unit[$i];
}

function echo_time_wasted($i = null) {
    global $start;
    $time = microtime(true) - $start;
    if ($i) {
        echo2 ("Идем по строке ".$i." Скрипт выполняется уже ".number_format($time, 2)." сек"." Памяти выделено в пике ".convert(memory_get_peak_usage(true)) );
    } else {
        echo2 ("Скрипт выполняется уже ".number_format($time, 2)." сек"." Памяти выделено в пике ".convert(memory_get_peak_usage(true)) );
    }

}

function print_r2($val) {
    echo '<pre>';
    print_r($val);
    echo  '</pre>';
    flush();
}

function echo2 ($str) {
    global $fp_log;
    fwrite($fp_log,date("d-m-Y H:i:s")." - ".$str."\n");
}

// Получение списка статей для парсинга, sitemap
//$sitemap = file_get_contents('http://therighthairstyles.com/sitemap.xml');
//$saw = new nokogiri($sitemap);
//$sitemap_items = $saw ->get('loc')->toArray();
//$sitemap_items2 = array();
//$i = 0;
//foreach ($sitemap_items as $sitemap_item) {
//    $saw = new nokogiri(file_get_contents($sitemap_item['#text']));
//    $sitemap_items2 = array_merge($sitemap_items2,$saw ->get('loc')->toArray());
//    sleep (2);
//    $i++;
//    if ($i % 10) echo_time_wasted($i);
//}
//foreach ($sitemap_items2 as $item) {
//    $sitemap_fin[] = $item['#text'];
//}
//
//$fp = fopen("sitemap_trh_com.txt",'w');
//foreach ($sitemap_fin as $item) {
//    fwrite($fp,$item. PHP_EOL);
//}
//fclose($fp);
//
//file_put_contents("sitemap_srlz_therighthair_com.txt",serialize($sitemap_fin));
// Конец Sitemap




//Debug file test
//$html = file_get_contents('http://therighthairstyles.com/30-best-hairstyles-and-haircuts-for-women-over-60-to-suit-any-taste/');
//$html = file_get_contents("hair1.html");

// Плохие символы кавычки одинарные, двойные.
$bad_symbols = array('â', 'â','â','â');
$bad_symbols2 = 'â' ;
//$z = 'Itâs a pity to cut beautiful curly hair. If itâs healthy and features sufficient thickness, why not to retain the length? Susan Sarandon shows us a good example of medium curly haircut for women over 60. Such whimsical waves can be achieved with mousse or any other curl-enhancer, applied to damp locks.';
//$p = 'New tendencies in hair styles for 2016 guide us towards livelier and more textured looks, like Diane Keatonâs âenergeticâ bob hairstyle. It appears spontaneous and present day, but fresh and appropriate for Dianeâs age and appearance.';
//$p = str_replace($bad_symbols,'\'',$p);

$sitemap = explode(PHP_EOL,file_get_contents("sitemap_trh_com.txt"));
$i = 0;
$counter_articles = 0;
foreach ($sitemap as $article) {
    $saw = new nokogiri(file_get_contents($article));
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
    if (in_array('0',$debug) ) {
        unset($items_all,$items_p,$items_h3,$items_img,$debug);
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
    } if (in_array('0',$debug)) {
        unset($items_all,$items_p,$items_h3,$items_img,$debug);
        echo "Не получилось получить итемы, что-то пошло не так. Урл $url".PHP_EOL;
    } else {
        $z = 0;
        foreach ($items_all[0]['h3'] as $item) {
            $images[$i]['url'] = $article;
            $images[$i]['h3'] = $item['#text'];
            $images[$i]['text'] = str_replace($bad_symbols2,'-',str_replace($bad_symbols,'\'',$items_all[0]['p'][$z]['#text']));
            $images[$i]['img_url'] = $items_all[0]['div'][$z]['img'][0]['src'];
            $images[$i]['img_alt'] = $items_all[0]['div'][$z]['img'][0]['alt'];
            $images[$i]['img_source'] = $items_all[0]['div'][$z]['p'][0]['a']['href'];
            if (strpos($images[$i]['text'],'â')) {
                $bad_img[$article] = $i;
            }
            echo $images[$i]['text'].PHP_EOL;flush();
            $i++;
            $z++;
        }
        echo "На странице $url получили ";
        echo '<pre>',print_r($debug,1),'</pre>'; flush();
        $fp = fopen("images_trh_com.csv",'w');
        foreach ($images as $image) {
            fputcsv($fp,$image,';');
        }
    }
}
$fp = fopen("images_trh_com.csv",'w');
foreach ($images as $image) {
    fputcsv($fp,$image,';');
}

echo "fixed!";