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
include("../nokogiri.php");

//$z = file_get_contents('../nokogiri_xml.xml');
//Убираем ебаное гавно
$z = str_replace('&#13;','',$z);
//file_put_contents('../nokogiri_xml2.xml',$z);
$doc = new DOMDocument;
$doc->load('../nokogiri_xml2.xml');
//$inspected_items = array();
//$items_to_inspect = array();
//function remove_text_nodes ($dom_doc)
//{
//    foreach ($dom_doc->childNodes as $childNode) {
//        if ($childNode->nodeType == 8 | $childNode->nodeType == 3) {
//            echo "Было элементов " . $dom_doc->childNodes->length . PHP_EOL;
//            flush();
//            $childNode->parentNode->removeChild($childNode);
//            echo "Стало элементов " . $dom_doc->childNodes->length . PHP_EOL;
//            flush();
//        }
//    }
//}
//remove_text_nodes($doc);
$items = $doc->getElementsByTagName('p');

//$items3 = $doc->getElementsByTagNameNS('*','*');
//foreach ($items3 as $node) {
//    if ($node->nodename == '#text') {
//        echo "xyu1";
//    }
//}

//Clean hlam
//$items2 = $doc->childNodes->item(0)->childNodes->item(0)->childNodes;
//for ($i = 0; $i < $items2->length; $i++) {
//    if ($items2->item($i)->nodeName == '#text') {
//        $items_to_remove[] = $items2->item($i);
//    }
//    if ($items2->item($i)->hasChildNodes() == true) {
//        echo "parent nodename ".$items2->item($i)->nodeName.PHP_EOL;
//        flush();
//        $try_img = $items2->item($i)->childNodes;
//        $z = 0 ;
//        foreach ($try_img as $img_nodes) {
//            echo "child nodename " . $try_img->item($z)->nodeName . PHP_EOL;
//            flush();
//            echo "child nodeValue " . $img_nodes->nodeValue . PHP_EOL;
//            flush();
//            $z ++;
//        }
//    }
//    echo "nodename " . $items2->item($i)->nodeName . PHP_EOL;
//    flush();
//    echo "nodeValue " . $items2->item($i)->nodeValue . PHP_EOL;
//    flush();
//    echo "getnodePath " . $items2->item($i)->getNodePath() . PHP_EOL;
//    flush();
//    echo "textContent " . $items2->item($i)->textContent . PHP_EOL;
//    flush();
//    echo "----" . PHP_EOL;
//    flush();
//}
//
//foreach ($items_to_remove as $hlam) {
//    $hlam->parentNode->removeChild($hlam);
//}

//$f = $doc->saveXML();
//file_put_contents('../noko_3.xml',$f);
// end clean hlam
$doc = new DOMDocument();
$doc ->loadXML(file_get_contents('../noko_3.xml'));
$h3_items = $doc->getElementsByTagName('h3');
foreach ($h3_items as $h3_item) {
    if ( $h3_item->nextSibling->nodeName == 'p' ) {
        $p_item_try = $h3_item->nextSibling;
        echo $p_item_try->nodeName.PHP_EOL;
        flush();
        echo $p_item_try->nodeValue.PHP_EOL;
        flush();
        if ($h3_item->nextSibling->nextSibling->getAttribute('class') == 'wp-caption aligncenter') {
            echo $h3_item->nextSibling->nextSibling->nodeValue.PHP_EOL;
            flush();
        }
    }

}

//$article = file_put_contents("../exmpl_cont.html",file_get_contents('http://therighthairstyles.com/30-great-short-hairstyles-for-black-women/'));
$article = file_get_contents("../exmpl_cont.html");
//$sitemap = explode(PHP_EOL,file_get_contents("../sitemap_trh_com.txt"));
$i = 0;
$counter_articles = 0;
$saw = new nokogiri(file_get_contents('http://therighthairstyles.com/30-great-short-hairstyles-for-black-women/'));
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
$p = str_replace($bad_symbols, '\'', $p);
$encoding = mb_detect_encoding($z);

echo $z . PHP_EOL;
flush();

iconv('CP-1252', 'UTF-8', $z);
$encoding = mb_detect_encoding($z);
echo $z . PHP_EOL;
flush();
iconv('windows-1252', 'UTF-8', $z);
$encoding = mb_detect_encoding($z);
echo $z . PHP_EOL;
flush();
iconv('ISO-8859-1', 'UTF-8', $z);
$encoding = mb_detect_encoding($z);
echo $z . PHP_EOL;
flush();
iconv('UTF-8', 'ISO-8859-1', $z);
$encoding = mb_detect_encoding($z);
echo $z . PHP_EOL;
flush();
iconv('UTF-8', 'CP-1252', $z);
$encoding = mb_detect_encoding($z);
echo $z . PHP_EOL;
flush();
iconv('UTF-8', 'windows-1252', $z);
$encoding = mb_detect_encoding($z);
echo $z . PHP_EOL;
flush();

?>