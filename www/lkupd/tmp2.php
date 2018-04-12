<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 02.04.2018
 * Time: 21:00
 * Замена singlepic и ngslider на галереи
 * [gallery type="rectangular" link="file" ids="106799 ,106800 ,106801"]
 */
require_once '../new/includes/functions.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
$dbname['wp'] = 'dev_houzz';
mysqli_connect2($dbname['wp']);
$domain = "http://ihouzz1.ru/";

//$tmp = file_get_contents('./debug/tmptext.txt');
//$tmp = preg_match_all('/\[ngslider\s?id=\s?"([\d,\s]*)"?.*?]/si', $tmp, $matches);
//$matches[1] = array_map('trim', $matches[1]);

$res = dbquery("SELECT `ID`,`post_content` FROM `wp_posts` WHERE `post_type` = 'post' AND `post_status` IN ('publish','private','trash') AND `post_content` LIKE '%singlepic%' OR `post_content` LIKE '%ngslider%';", 1);

foreach ($res as $row) {
    $content_fin = $row[1];

    if (stripos($row[1], 'singlepic')) {
        preg_match_all('/\[singlepic\s?id=\s?(\d*).*?]/si', $row[1], $matches);
        $matches[1] = array_map('trim', $matches[1]);

        $i = 0;
        foreach ($matches[0] as $tmp) {
            $matches[2][$i] = stripos($row[1], $tmp);
            $i++;
        }

        //Разбивка по галереям
        $gallery_num = 0;
        for ($i = 0; $i < count($matches[0]); $i++) {
            $tmp = strlen($matches[0][$i]) + $matches[2][$i] + 5; //Если расстояние между singlepic тегами больше 5 символов, то значит уже другая галерея идет
            if ($tmp > $matches[2][$i + 1]) {
                $matches[3][$i] = $gallery_num;
                if ($i + 1 < count($matches[0])) { //Чтобы не было лишнего элемента массива
                    $matches[3][$i + 1] = $gallery_num;
                }
            } else {
                $matches[3][$i] = $gallery_num;
                $gallery_num++;
                if ($i + 1 < count($matches[0])) { //Чтобы не было лишнего элемента массива
                    $matches[3][$i + 1] = $gallery_num;
                }
            }
        }

        for ($i = 0; $i < count($matches[0]); $i++) {
            if (isset($matches[3][$i])) {
                $tmp = array_keys($matches[3], $matches[3][$i]);
                $z = 0;
                foreach ($tmp as $key => $value) {
                    if (is_numeric($matches[1][$value])) {
                        $img_id = $matches[1][$value] + 100000;
                    } else {
                        echo2("$row[0] - trouble!");
                    }
                    if ($z == 0) {
                        $content_fin = str_replace($matches[0][$value], '[gallery type="rectangular" link="file" ids="' . $img_id, $content_fin);
                    } else if ($z + 1 == count($tmp)) {
                        $content_fin = str_replace($matches[0][$value], ",$img_id\"]" . PHP_EOL, $content_fin);
                    } else {
                        $content_fin = str_replace($matches[0][$value], ",$img_id", $content_fin);
                    }
                    $z++;
                    unset($matches[3][$value]);
                }
            }
        }
    }
    if (stripos($content_fin, 'ngslider')) {
        preg_match_all('/\[ngslider\s?id=\s?"([\d,\s]*)"?.*?]/si', $content_fin, $matches);
        foreach ($matches[0] as $key => $tmp) {
            $tmp2 = explode(',', $matches[1][$key]);
            $tmp2 = array_map('trim', $tmp2);
            foreach ($tmp2 as $tmp3) {
                $tmp4 .= 100000 + $tmp3 . ',';
            }
            $tmp4 = substr($tmp4, 0, -1);
            $content_fin = str_ireplace($tmp, '[gallery type="rectangular" link="file" ids="' . $tmp4 . '"]', $content_fin);
            unset($tmp4);
        }
    }
    $content_fin = mysqli_real_escape_string($link, $content_fin);
    dbquery("UPDATE `$dbname[wp]`.`wp_posts` SET `post_content` = '$content_fin' WHERE `ID` = $row[0];");

    $p++;
    if ($p % 100 == 0) {
        echo_time_wasted($p . "/" . count($res));
    }
}