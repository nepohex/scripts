<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 01.12.2016
 * Time: 20:43
 */
//include "config.php";
//include "mysqli_connect.php";
$start = microtime(true);
include ("../nokogiri.php");
//$article = file_put_contents("../exmpl_cont.html",file_get_contents('http://therighthairstyles.com/30-great-short-hairstyles-for-black-women/'));
$article = file_get_contents("../exmpl_cont.html");
//$sitemap = explode(PHP_EOL,file_get_contents("../sitemap_trh_com.txt"));
$i = 0;
$counter_articles = 0;
$saw = new nokogiri->fromHtmlNoCharset(file_get_contents('http://therighthairstyles.com/30-great-short-hairstyles-for-black-women/'));
$tmp[] = $saw->get('div.post_cont')->toArray();
$tmp[] = $saw->get('div.post_cont')->toXml();
    $counter_articles++;
    $expression = 'div.post_cont:nth-child(' . $i . ')';
$tmp[] = $saw->get($expression)->toArray();
    $i++;
    $expression = 'div.post_cont:nth-child(' . $i . ')';
    $tmp2 = $saw->get($expression)->toArray();
$tmp[] = $saw->get('div.post_cont:nth-child(even)')->toArray();
$tmp[] = $saw->get('div.post_cont h3 :nth-child(even)')->toArray();
$tmp[] = $saw->get('div.post_cont h3:nth-child(even)')->toArray();
$tmp[] = $saw->get('div.post_cont h3:nth-child()')->toArray();
$tmp[] = $saw->get('div.post_cont h3:nth-child()')->toArray();
$tmp[] = $saw->get('div.post_cont:nth-child(5)')->toArray();
$tmp[] = $saw->get('div.post_cont:nth-child(10)')->toArray();
$tmp[] = $saw->get('div.post_cont:last-child')->toArray();
$tmp[] = $saw->get('div.post_cont:first-child')->toArray();

        $item[] = $tmp;
        $i++;

//$s = file_get_contents("http://therighthairstyles.com/30-great-short-hairstyles-for-black-women/");
$z = 'Itâs a pity to cut beautiful curly hair. If itâs healthy and features sufficient thickness, why not to retain the length? Susan Sarandon shows us a good example of medium curly haircut for women over 60. Such whimsical waves can be achieved with mousse or any other curl-enhancer, applied to damp locks.';
$p = 'New tendencies in hair styles for 2016 guide us towards livelier and more textured looks, like Diane Keatonâs âenergeticâ bob hairstyle. It appears spontaneous and present day, but fresh and appropriate for Dianeâs age and appearance.';
$p = str_replace($bad_symbols,'\'',$p);
$encoding = mb_detect_encoding($z);

echo $z.PHP_EOL; flush();

iconv('CP-1252','UTF-8',$z);
$encoding = mb_detect_encoding($z);
echo $z.PHP_EOL; flush();
iconv('windows-1252','UTF-8',$z);
$encoding = mb_detect_encoding($z);
echo $z.PHP_EOL; flush();
iconv('ISO-8859-1','UTF-8',$z);
$encoding = mb_detect_encoding($z);
echo $z.PHP_EOL; flush();
iconv('UTF-8','ISO-8859-1',$z);
$encoding = mb_detect_encoding($z);
echo $z.PHP_EOL; flush();
iconv('UTF-8','CP-1252',$z);
$encoding = mb_detect_encoding($z);
echo $z.PHP_EOL; flush();
iconv('UTF-8','windows-1252',$z);
$encoding = mb_detect_encoding($z);
echo $z.PHP_EOL; flush();

?>