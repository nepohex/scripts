<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 03.04.2018
 * Time: 20:26
 * Найти и снять с публикации записи без SEO трафика ( данные Яндекс метрики 2016-2018 годы менее 100 входов с поиска )
 * 04-04-2018 12:26:17 - Строк / Постов выгрузили/ внешних / обновили статус 1680 / 1330 / 616 / 713
 * статус поставили private
 */
require_once '../new/includes/functions.php';
include_once 'C:\OpenServer\domains\scripts.loc\www\parser\simpledom\simple_html_dom.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
$dbname['wp'] = 'dev_lk_old';
mysqli_connect2($dbname['wp']);
$domain = "http://ladykiss1.ru/";

//$tmp = file_get_contents('./debug/tmptext.txt');
//$tmp = str_get_html($tmp);
//foreach ($tmp->find('a[href]') as $element) {
//    echo $element->href . PHP_EOL;
//}
//preg_match_all('/<a.*(ladykiss.*?)<\/a>/si', $tmp, $matches);

//$tmp = '2011-10-01 16:09:07';
//$tmp2 = date('Y-m-d H:i:s',time());
//$tmp3 = strtotime('- 6 month');
//$tmp4 = date('Y-m-d H:i:s',$tmp3);

$res = csv_to_array2('c:\OpenServer\domains\scripts.loc\www\lkupd\debug\lk_noseotraff_2016-18.csv', ';', null, 1);

foreach ($res as $row) {
    $r++;
    if (stripos($row[1], 'html') && !stripos($row[1], '?') && !stripos($row[1], '#')) {
        $tmp2 = parse_url($row[1], PHP_URL_PATH);
        $tmp2 = last(explode('/', $tmp2));
        $tmp2 = first(explode('.', $tmp2));
        $tmp2 = dbquery("SELECT `ID`, `post_content`,`post_date` FROM `$dbname[wp]`.`wp_posts` WHERE `post_name` = '$tmp2';", 1);
        if ($tmp2) {
            $p++;
            $ID = $tmp2[0][0];
            if (strtotime($tmp2[0][2]) < strtotime('- 6 month')) {
                $tmp = str_get_html($tmp2[0][1]);
                if ($tmp) {
                    foreach ($tmp->find('a[href]') as $element) {
                        if (stristr($element->href, '#') OR strpos($element->href, 'ladykiss')) {
                        } else {
                            $external_links = TRUE;
                            $z++;
                            break;
                        }
                    }
                    if (!$external_links) {
                        dbquery("UPDATE `$dbname[wp]`.`wp_posts_new` SET `post_status` = 'private' WHERE `ID` = $ID;");
                        $i++;
                    }
                    unset($external_links);
                }
            } else {
                $d++;
            }
        }
    }
}
echo2("Строк / Постов выгрузили/ внешних / слишком молодые (младше 6 мес) / обновили статус $r / $p / $z / $d / $i ");