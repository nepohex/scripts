<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 29.08.2017
 * Time: 16:36
 * SELECT COUNT(DISTINCT `id`) as `popular_img`, `image_url`, `id` FROM `google_images` WHERE `size` NOT IN (0,1,-1) GROUP BY `size`
 * ORDER BY `popular_img`  DESC LIMIT 10
 *
 * SELECT `semrush_keys`.* FROM `semrush_keys` LEFT JOIN `google_images_relations` ON `google_images_relations`.`query_id` = `semrush_keys`.`key_id` WHERE `semrush_keys`.`key` LIKE '%ponytail%' ORDER BY `semrush_keys`.`key_id` DESC LIMIT 10
 */
require_once('../includes/functions.php');

$fp_log = "log.txt";

$db_usr = 'root';
$db_name = 'image_index';

//debug
ini_set('ERROR_REPORTING', E_ALL);
mysqli_connect2($db_name);
//Сколько картинок для каждого ключа грузить, позиция в гугл поиске
$max_pos = 20;
//dbquery("TRUNCATE `google_images_relations`;");
$double_log = 1;
$debug_mode = 1;
//

$path_dir = 'f:\Dumps\SEO\sort_name';
$json_list = scandir($path_dir);
$json_count = count($json_list) - 2;

$k = array(
    'key' => 'straightening hair cuts',
    'pos' => 11,
    'url' => 'https://www.pinterest.com/explore/haircuts-straight-hair/',
    'title' => 'Best 25+ Haircuts straight hair ideas on Pinterest | Straight hair ...',
    'img_url' => 'https://i.pinimg.com/736x/af/99/2b/af992bbe421ce49f34fb10fd970db378--haircuts-straight-hair-naturally-straight.jpg',
    'w' => 500,
    'h' => 500,
    'size' => 29271,
);
$i = 0;
foreach ($json_list as $json) {
    if (strstr($json, ".json")) {
        $data = json_decode(file_get_contents($path_dir . '/' . $json));
        if (is_object($data)) {
            $i++;
            echo2("#$i/$json_count Начинаем обработку $json");
            $fp = fopen("no_key_id.txt", "a+");
            foreach ($data as $key => $result) {
                $key = mysqli_real_escape_string($link, $key);
                $key_id = dbquery("SELECT `key_id` FROM `semrush_keys` WHERE `key` = '$key';");
                if ($key_id > 10) {
                    foreach ($result->results as $position) {
                        $item['key'] = $key;
                        $item['pos'] = $position->pos;
                        if ($item['pos'] > $max_pos) {
                            break;
                        }
                        $item['url'] = $position->url;
                        $item['title'] = $position->extra_data->title;
                        $item['img_url'] = $position->extra_data->img_url;
                        $item['w'] = $position->extra_data->json->ow;
                        $item['h'] = $position->extra_data->json->oh;
                        $img_url = mysqli_real_escape_string($link, $item['img_url']);

                        //Основной цикл
                        $domain_id = get_domain_id($img_url);
                        $image_id = write_img($img_url, $domain_id[0], $domain_id['new'], $images_imported, $doubles_images);
                        write_relation($image_id, $key_id, $item['pos']);
//                        // Чекаем есть ли запись об этой картинке в базе. Если есть, то пишем только записи ключ-картинка, но не сами картинки.
//                        if (($image_id = dbquery("SELECT `image_id` FROM `google_images` WHERE `image_url` = '$img_url';")) == false) {
//                            $tmp_time = microtime(true);
//                            // Очень долго хедеры получать!
////                            $item['size'] = remote_file_size($position->extra_data->img_url);
////                            $item['size'] > 10 ? $counter_get_size++ : $counter_fail_headers++;
//                            $item['size'] = '-2';
//                            $headers_time += microtime(true) - $tmp_time;
//                            //Запись с width / height / size => слишком много места
////                            dbquery("INSERT INTO `google_images` (image_url,width,height,size) VALUES ('$img_url',$item[w],$item[h],$item[size]);");
//                            dbquery("INSERT INTO `google_images` (image_url,width,height,size) VALUES ('$img_url',0,0,0);");
//                            $image_id = mysqli_insert_id($link);
//                            $images_imported++;
//                        } else {
//                            $doubles_images++;
//                        }
////                        $item['size'] = '-2';
//                        dbquery("INSERT INTO `google_images_relations` (image_id,key_id,position) VALUES ($image_id,$key_id,$item[pos]);", null, TRUE);
//                        unset($image_id);
                    }
                } else {
                    fputs($fp, "$key" . PHP_EOL);
                    $no_keys_found++;
                }
                unset($key_id);
            }
            fclose($fp);
            $images_total += $images_imported;
            $doubles_total += $doubles_images;
            $total_headers_time += $headers_time;
            echo_time_wasted(null, "Закончили с $json, картинок добавили $images_imported, дублей $doubles_images , времени на headers $headers_time. Всего картинок $images_total , дублей $doubles_total . Failed headers $counter_fail_headers , success $counter_get_size . Ключей для которых не нашли key_id в таблице semrush_keys $no_keys_found");
            unset ($images_imported, $doubles_images, $headers_time, $data);
        } else {
            echo2("Cant get json data!");
        }
    }
    $log_json = fopen('json_done.txt', 'a');
    fputs($log_json, $json . PHP_EOL);
    fclose($log_json);
}
echo_time_wasted($i, "Всего картинок закачали");
/**
 * Get Remote File Size
 *
 * @param sting $url as remote file URL
 * @return int as file size in byte
 */
function remote_file_size($url)
{
    # Get all header information
    $data = get_headers($url, true);
    # Look up validity
    if (!is_array($data)) {
        return "-1";
    }
    if (isset($data['Content-Length'])) {
        # Return file size
        return (int)$data['Content-Length'];
    } else {
        return 0;
    }
}

function get_domain($url, $path = null)
{
    //debug
//    if (stripos($url,"attach")) {
//        echo ($url);
//    }
    $tmp = parse_url($url);
    switch ($tmp['scheme']) {
        case 'http':
            $z = 0;
            break;
        case 'https':
            $z = 1;
            break;
    }
    $domain = $z . $tmp['host'];
    if ($path) {
        $path = '';
        if ($tmp['path']) {
            $path .= $tmp['path'];
        }
        if ($tmp['query']) {
            $path .= "?" . $tmp['query'];
        }
        if ($tmp['fragment']) {
            $path .= "#" . $tmp['fragment'];
        }
        $domain = $path;
    }
    return $domain;
}

function get_domain_id($url)
{
    global $link;
    $domain = get_domain($url);
    $tmp = dbquery("INSERT INTO `google_images_domains2` (`domain`, `images_count`) VALUES ('$domain','');", null, true, null, TRUE);
    if ($tmp > 0) {
        $domain_id[] = dbquery("SELECT MAX(`domain_id`) FROM `google_images_domains2`;");
        $domain_id['new'] = TRUE;
    } else {
        $domain_id[] = dbquery("SELECT `domain_id` FROM `google_images_domains2` WHERE `domain` = '$domain';");
        $domain_id['new'] = FALSE;
    }
    return $domain_id;
}

function write_img($url, $domain_id, $new_domain = TRUE, &$images_imported, &$doubles_images)
{
    global $link;
    $url_path = get_domain($url, 1);
    if ($new_domain) {
        dbquery("INSERT INTO `google_images2` (`domain_id`,`image_url`) VALUES ('$domain_id','$url_path');");
        $images_imported++;
        return mysqli_insert_id($link);
    } else if (($image_id = dbquery("SELECT `image_id` FROM `google_images2` WHERE `domain_id` = $domain_id AND `image_url` = '$url_path';")) == false) {
        dbquery("INSERT INTO `google_images2` (`domain_id`,`image_url`) VALUES ('$domain_id','$url_path');", null, null, null, true);
        $images_imported++;
        return mysqli_insert_id($link);
    } else {
        return $image_id;
        $doubles_images++;
    }
}

function write_relation($image_id, $key_id, $position)
{
    if ($relation_id = dbquery("SELECT `relation_id` FROM `google_images_relations2` WHERE `key_id` = $key_id AND `image_id` = $image_id;") == FALSE) {
        dbquery("INSERT INTO `google_images_relations2` (image_id,key_id,position) VALUES ($image_id,$key_id,$position);", null, TRUE);
    }
}