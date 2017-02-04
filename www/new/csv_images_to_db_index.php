<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 26.11.2016
 * Time: 1:45
 * На вход данные в CSV, обработка в 1млн (100 мь) строк прошла без проблем:
 * path    | size (необязательно, никак не обрабатывается)
 * maxfashionco.com\wp-content\uploads\2015\06\Pretty-Nail-Art-Designs-with-Blue-and-Rainbow-Motif-Ideas-174x174.jpg | 55000
 */
header('Content-Type: text/html; charset=utf-8');
$start = microtime(true);

$load_only_uniq = false; // Если ставить TRUE то загрузка будет очень долгой, по 35 сек на каждые 100 картинок с проверкой каждой на уникальность в пределах базы.

$db_usr = 'root';
$db_name = 'image_index';
$db_pwd = '';
$link = mysqli_init();

$csv_file = 'F:\Dumps\downloaded sites\import_db_big6.csv';
$down_sites_path_prefix = "f:\\Dumps\\downloaded sites\\";
$filename_filters = array('hair', 'beard', 'updo', 'graduat', 'brunett', 'bun', 'bang', 'ponytail', 'ombre', 'brunet', 'bob', 'pixie', 'blond', 'layer', 'curl', 'ombre', 'waves', 'straight', 'highlight', 'mohawk', 'fishtail', 'blunt', 'headband', 'pompadour', 'dread', 'waterfall', 'balayage', 'femini','undercut','messy','chopp','asymmet','twist','burgund','length','african','american','older','face','pony','tail','edgy','copper');
$patterns = array('/[0-9]+x[0-9]+/i', '/.+descr\.wd3/i');

if (!$link) {
    die('mysqli_init завершилась провалом');
}

if (!mysqli_real_connect($link, 'localhost', $db_usr, $db_pwd, $db_name)) {
    die('Ошибка подключения (' . mysqli_connect_errno() . ') '
        . mysqli_connect_error());
}

function dbquery($queryarr)
{
    global $link;
    if (is_array($queryarr)) {
        foreach ($queryarr as $query) {
            $sqlres = mysqli_query($link, $query);
            if ($error = mysqli_error($link)) {
                echo("Mysqli error $error в запросе $query");
                flush();
            }
        }
    } else {
        mysqli_query($link, $queryarr);
        if ($error = mysqli_error($link)) {
            echo("Mysqli error $error в запросе $queryarr");
            flush();
        }
    }
}

$query = "SELECT * FROM `domains`";
$sqlres = mysqli_query($link, $query);
if ($mysql_error = mysqli_error($link)) {
    mysqli_error($link);
    $mysql_error = false;
}

while ($row = mysqli_fetch_assoc($sqlres)) {
    $db_domains[] = $row;
    $domains[] = $row['domain'];
}

$c_db_domains = count($db_domains);

$query = "SELECT `id` FROM `images` ORDER BY `id`  DESC LIMIT 1";
$sqlres = mysqli_query($link, $query);
$tmp = mysqli_fetch_row($sqlres);
$images_offset = $tmp[0];
$images_offset++;

$fp = fopen($csv_file, 'r+');

$i = 0;
$q = 1;
while (($data = fgetcsv($fp)) !== FALSE) {
    $tmp = explode(";", $data[0]);
    $csv_import = explode("\\", $tmp[0]);
    if (!(in_array($csv_import[0], $domains))) {
        if ($csv_import[1] == "wp-content") {
            $wp_prefix = "\\wp-content\\uploads\\";
            $is_wp = 1;
            $site_dir = addslashes($down_sites_path_prefix . $csv_import[0] . $wp_prefix);
            $c_db_domains++;
            $query = "INSERT INTO `domains` VALUES ('" . $c_db_domains . "','" . $csv_import[0] . "','" . $site_dir . "'," . $is_wp . ");";
            $sqlres = mysqli_query($link, $query);
            $domains[] = $csv_import[0];
            $db_domains[$c_db_domains]['site_id'] = $c_db_domains;
            $db_domains[$c_db_domains]['domain'] = $csv_import[0];
            $db_domains[$c_db_domains]['site_dir'] = stripslashes($site_dir);
            $db_domains[$c_db_domains]['is_wp'] = $is_wp;
        } else {
            $wp_prefix = false; // Дописать позже
            $is_wp = 0;
        }
    }

    foreach ($filename_filters as $filename_filter) {
        if (stripos(end($csv_import), $filename_filter)) {
            $valid_name = 1;
            break;
        } else {
            $valid_name = 0;
        }
    }
    if ($valid_name == 1) {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, end($csv_import))) {
                $valid_name = 0;
                break;
            }
        }
    }
    if ($valid_name == 1) {
        foreach ($db_domains as $db_domain) {
            if ($db_domain['domain'] == $csv_import[0]) {
                $site_id = $db_domain['site_id'];
                break;
            }
        }
        $img_path_piece = addslashes(implode("\\", array_slice($csv_import, 3, count($csv_import) - 4))); //Если первая цифра в array_splice = 1 ,то переменная с wp-content\uploads, если 3, то без
        $img_name = array_slice($csv_import, -1);
        if ($load_only_uniq == true) {
            $query = "SELECT COUNT(*) FROM `images` LEFT JOIN `domains` ON (domains.`site_id` = images.`site_id`) where `filename` ='" . $img_name[0] . "'";
            $sqlres = mysqli_query($link, $query);
            $r = mysqli_fetch_row($sqlres);
            if ($r[0] == 0) {
                $query = "INSERT INTO `images` VALUES (" . $images_offset . "," . $site_id . ",'" . $img_path_piece . "','" . $img_name[0] . "');";
                dbquery($query);
                $counter_new_images++;
                unset($sqlres);
                $images_offset++;
                if (mysqli_error($link)) {
                    echo mysqli_error($link);
                    flush();
                    exit();
                }
            } else {
                $filesize_new_image = filesize($down_sites_path_prefix . '/' . $tmp[0]);
                $query = "SELECT * FROM `images` LEFT JOIN `domains` ON (domains.`site_id` = images.`site_id`) where `filename` ='" . $img_name[0] . "'";
                $sqlres = mysqli_query($link, $query);
                while ($row = mysqli_fetch_assoc($sqlres)) {
                    $full_img_path = $down_sites_path_prefix . $row['domain'] . '/' . $row['image_path'] . '/' . $row['filename'];
                    $filesizes[] = filesize($full_img_path);
                    if (end($filesizes) == $filesize_new_image) {
                        $queries[] = "INSERT INTO `image_size` VALUES (" . $row['id'] . "," . end($filesizes) . ",'','');";
                        break;
                    } else {
                        $queries[] = "INSERT INTO `image_size` VALUES (" . $row['id'] . "," . end($filesizes) . ",'','');";
                    }
                    if (count($filesizes) == $r[0]) {
                        $queries[] = "INSERT INTO `images` VALUES (" . $images_offset . "," . $site_id . ",'" . $img_path_piece . "','" . $img_name[0] . "');";
                        $images_offset++;
                        $counter_new_images++;
                    }
                }
                dbquery($queries);
            }
        } else {
            $query = "INSERT INTO `images` VALUES (" . $images_offset . "," . $site_id . ",'" . $img_path_piece . "','" . $img_name[0] . "');";
            dbquery($query);
            $counter_new_images++;
            unset($sqlres);
            $images_offset++;
            if (mysqli_error($link)) {
                echo mysqli_error($link);
                flush();
                exit();
            }
        }
    }
    $i++;
    if (is_int($i / 10000)) {
        echo "<br>Уже загружено _ " . $i . " _ строк, едем дальше! <br>";
        $time = microtime(true) - $start;
        printf('Скрипт выполняется уже %.2F сек.', $time);
        flush();
    }
}
echo "Загрузили новых картинок $counter_new_images / $i";

?>