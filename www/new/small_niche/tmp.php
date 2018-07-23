<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 26.04.2018
 * Time: 23:09
 */
include "../includes/functions.php";
$double_log = 1;
$debug_mode = 1;

$site_name = 'mfa_babyshower1.com'; // Без слешей, только домен
$db_name = $site_name;
$dbname = array(
    'spin' => 'hair_spin',
    'image' => 'image_index',
    'keys' => 'image_index',
    'key' => 'image_index',
    'wp' => $db_name,
);

$tmp = 'car 345345baby zina-s-magasinz|dgdfig|idet^fgfgh|bnbn+ghfgh .jpg obama car money';
$tmp = preg_replace('/[^\w\d]/i', ' ', $tmp); //Замена всех не слов пробелами
$tmp = dictionary_check($tmp, '-', TRUE);

define('LIMIT_TIME_WORDS_COUNT', '20');
$files = array_fill(1, 100, rand(1, 100));
foreach ($files as $item) {
    //todo Шаг надо упразднить вообще. Это временное прерывание.
    if (LIMIT_TIME_WORDS_COUNT) {
        if (!isset($limit_start_time)) {
            $limit_start_time = number_format(microtime(true) - $start);
        }
        if (number_format(microtime(true) - $start + $limit_start_time) > LIMIT_TIME_WORDS_COUNT) {
            echo_time_wasted("Прерываем подсчет Использованных слов по ограничителю времени");
            break;
        }
    }
}
file_put_contents(__DIR__ . '/debug_data/top_words_part_' . $part . '_theme_' . $theme . '_srlz.txt', serialize($final));


if (LIMIT_TIME_WORDS_COUNT) {
    if (!isset($limit_start_time)) {
        $limit_start_time = number_format(microtime(true) - $start);
    }
    if (number_format(microtime(true) - $start + $limit_start_time) > LIMIT_TIME_WORDS_COUNT) {
        echo_time_wasted("Прерываем подсчет Использованных слов по ограничителю времени");
        echo2("fin!");
    }
}

$files = scandir('f:\Dumps\google_images\coloring');
shuffle($files);
foreach ($files as $file) {
    $i++;
//    is_file('f:\Dumps\google_images\coloring\\' . $file);
//    $debug['is_file1'] += debug_process_time();

    file_exists('f:\Dumps\google_images\coloring\\' . $file);
    $debug['file_ex'] += debug_process_time();

    if ($i % 2000 == 0) {
        echo_time_wasted();
        break;
    }
}
shuffle($files);
foreach ($files as $file) {
    $i++;
    is_file('f:\Dumps\google_images\coloring\\' . $file);
    $debug['is_file1'] += debug_process_time();

//    file_exists('f:\Dumps\google_images\coloring\\' . $file);
//    $debug['file_ex'] += debug_process_time();

    if ($i % 2000 == 0) {
        echo_time_wasted();
        break;
    }
}

for ($i = 0; $i < 1000; $i++) {
    is_file('f:\tmp\\' . rand(0, 10000000) . '.txt');
    $debug['is_file1'] += debug_process_time();
    file_exists('f:\tmp\\' . rand(0, 10000000) . '.txt');
    $debug['file_ex'] += debug_process_time();
}
$db_name = $site_name;
// База данных с картинками
$db_name_img = 'image_index';
$db_host = 'localhost';
$db_pwd = '';
$wp_conf_tpl = 'wp_conf_empty.txt';
$wp_conf_cache_tpl = 'wp-cache-conf_empty.txt';
// База со спинами
$db_name_spin = 'hair_spin';
$dbname['image'] = 'image_index';
mysqli_connect2($dbname['image']);


$tmp = 'car 345345baby zina-s-magasinz|dgdfig|idet^fgfgh|bnbn+ghfgh .jpg obama car money';
$tmp = preg_replace('/[^\w\d]/i', ' ', $tmp); //Замена всех не слов пробелами
$tmp = dictionary_check($tmp, '-', TRUE);

/** На вход строка, проверка по словарю каждого слова, удаление лишних, и склейка слов сепаратором.
 * @param $string
 * @param string $separator
 * @param $replace_numbers
 * @return bool|mixed|string
 */
function dictionary_check($string, $separator = '_', $replace_numbers)
{
    global $dbname;
    $tmp = preg_replace('/[^\w\d]/i', ' ', $string); //Замена всех не слов пробелами
    $tmp = str_replace('_', ' ', $tmp); //Замена Нижних подсчеркиваний (underscore) потому как \w\d не воспринимает!
    if ($replace_numbers) {
        $tmp = preg_replace('/\d/', ' ', $tmp); //цифры
    }
    $tmp = preg_replace('/\s{2,}/', ' ', $tmp); //Двойные и более пробелы на пробел
    $tmp = trim($tmp);
    $arr = explode(' ', $tmp);
    foreach ($arr as $key => &$word) {
        if (($tmp2 = dbquery("SELECT `id` FROM `$dbname[image]`.`dictionary` WHERE `word` = '$word';")) == FALSE) {
            unset ($arr[$key]);
        }
    }
    if (count($arr) > 0) {
        $tmp = implode($separator, $arr);
        return $tmp;
    } else {
        return FALSE;
    }
}

//region Prepare Texts
$tmp = file('F:/tmp/babyshower_texts.txt', FILE_IGNORE_NEW_LINES);
//^[\d]{1}\.  Regex для замены нумерации строк, менял в блокноте!
$tmp = array_unique($tmp);
foreach ($tmp as $key => $item) {
    $item = preg_replace('/^[0-9]+./', '', $item);
    $item = trim(preg_replace('/\s{2,}/', '', $item));
    if (count_strlen_html($item) < 30) {
        unset($tmp[$key]);
        @$low_len++;
    } else {
        if (striposa($item, array('getty', 'istock', 'shutterstock', 'photograph:', 'flickr', 'image via', 'image source', 'www', '.com'))) {
            unset($tmp[$key]);
            @$bad_words++;
        }
    }
    if ($tmp[$key]) {
        mb_strlen($tmp[$key]) < 150 ? $facts[] = $tmp[$key] : $texts[] = $tmp[$key];
    }
}
echo2("$low_len < Del low len / Bad words > $bad_words ");
echo2(count($facts) . " < Facts / Texts > " . count($texts));
$tmp = implode(PHP_EOL, $tmp);
$tmp2 = implode(PHP_EOL, $facts);
$tmp3 = implode(PHP_EOL, $texts);
file_put_contents('F:/tmp/babyshower_all_clean.txt', $tmp);
file_put_contents('F:/tmp/babyshower_facts_clean.txt', $tmp2);
file_put_contents('F:/tmp/babyshower_texts_clean.txt', $tmp3);
//endregion


$post_id = 3554506;
$rand_posts_ids = range($post_id - 2000, $post_id + 2000);
$tmp = unserialize('a:1:{s:64:"/wp-content/uploads/2018/06/070bec7e6499449609a6cc5a6459f426.jpg";i:205420;}');
$arr[] = '213';
$arr[] = '213';
$arr2[] = '213';
$z = array_unique($arr);
$z = count(array_unique($arr2));
$checksum = crc32("T56456hgm6456mnhe quick dfgdfgdfbrowndfgdfgd fox 7676jumpdgdfgdfgdfged overhjhgjgh the lhgjghjazy dog.");
printf("%u\n", $checksum);

$csv[$tmp2][2] = 1000130;
if ($top_size < $csv[$tmp2][2]) {
    $top_size = $csv[$tmp2][2];
    $top_id = $tmp2;
}

$tmp = array('and', 'print', 'coloring', 'colorings', 'page', 'pages', '0', 'jpg', 'page', 'free', 'printable', 'for', 'with', 'kids', 'of', 'to', 'color', 'the', 'on', 'gif', 'book', 'colouring', 'png', 'adults', 'in', 'sheets', 'pictures', 'ideas', 'online', 'best', 'download', 'disney', 'christmas', 'a', 'day', 'design', 'adult', 'baby', 'images', 'cute', 'princess', 'new', 'halloween', 'little', 'books', 'picture', 'animals', 'animal', 'your', '1', 'sheet', 'by', 'beautiful', 'my', 'com', 'amazing', 'lego', 'cool', 'girl', 'me', 'awesome', 'mouse', 'at', 'monster', 'easter', 'games', 'truck', 'letter', 'star', 'printables', 'preschool', 'about', 'happy', 'pony', 'bible', 'cartoon', 'mandala');

foreach ($tmp as $key => &$item) {
    $reg = "/^$item" . '[a-z]{0,3}$/i';
//    preg_match($reg, $tmp, $matches);
    $matches = preg_grep($reg, $tmp);
    if (count($matches) > 1) {
        $del[] = $item;
        $del[$item] = $matches;
        unset ($tmp[$key]);
    }
}


$num = rand(100, 999);
$dir = 'f:\Dumps\downloaded sites\babyshowerpin.com';

$fnames = getDirContents($dir);

foreach ($fnames as $fname) {
    $tmp2 = pathinfo($fname);
    $tmp = $tmp2['filename'];
    if (is_file($new_dir . '/' . $tmp)) {
        $new_name = rand(100, 999) . '_' . $tmp;
    } else {
        $new_name = $tmp;
    }
    rename($fname, $new_dir . '/' . $new_name);
}
echo_time_wasted();
//region
$db_name = 'image_index2';
$t_name = 'image_doubles';
$theme = 3;
//Фикс базы данных если паренты неверно определились
//!!ВНИМАНИЕ РАСКОММЕНТИРОВАТЬ UPDATE
//для начала прогнать и посмотреть все ли ОК с базой (количество $f должно быть 0), если не 0, то $f = $s , значит фикс поможет.
echo2("Начинаем проверять связи в базе установлены верно или нет. Если нулевые цифры - все ок. Если нет, то связей должно быть одинаковое количесттво. ");
$query = "SELECT `id`, `new_name`, `parent_id` FROM `$db_name`.`$t_name` WHERE `theme` = $theme;";
dbquery("SELECT COUNT(*) FROM `$db_name`.`$t_name`;");

$i = 0;
if ($result = mysqli_query($link, $query)) {

    /* извлечение ассоциативного массива */
    while ($row = mysqli_fetch_assoc($result)) {
        $i++;
        if ($row['new_name'] == FALSE) {
            $tmp = dbquery("SELECT `new_name`,`parent_id` FROM `$db_name`.`$t_name` WHERE `id` = $row[parent_id];");
            $tmp = $tmp[0];
            if ($tmp['new_name'] == FALSE) {
                @$f++;
                if (($tmp2 = dbquery("SELECT `new_name` FROM `$db_name`.`$t_name` WHERE `id` = $tmp[parent_id]")) !== FALSE) {
                    @$s++;
//                    dbquery("UPDATE `$db_name`.`$t_name` SET `parent_id` = $tmp[parent_id] WHERE `id` = $row[id];");
                }
            }
        }
        $i % 5000 == 0 ? echo_time_wasted($i, "Битых связей ( $f ) | Есть возможность их поправить ( $s )") : '';
    }
}
//endregion


//region Удаление дублей после DUPImage по W/h ratio
$arr = csv_to_array2('f:\tmp\coloring_all.csv', ',', null, true);
$i = 0;
$group_trigger = 1; //Номер группы, устанавливаю 1 для удобства 1го обхода
$g = 0; //Итератор группы, номер файла

foreach ($arr as $item) {
    $i++;
    list($group, $full_path, $size, $date, $width, $height, $similarity, $checked) = $item;
//    $new_name = basename($full_path);
    if ($group_trigger == $group && @$g == 0) {
        $group_ratio = round($width / $height, 1);
        $g = 1;
    } else if ($group_trigger == $group && @$g > 0) {
        $whratio = round($width / $height, 1);
        if ($whratio !== $group_ratio) {
            $bad_group[] = $group;
            $bad_group = array_unique($bad_group);
        }
        $g++;
    } else if ($group_trigger < $group) {
        $group_trigger = $group;
        $group_ratio = round($width / $height, 1);
        $g = 1;
    }
    $i % 5000 == 0 ? echo_time_wasted($i, " / " . count($bad_group)) : '';
}

foreach ($arr as $key => &$item) {
    list($group, $full_path, $size, $date, $width, $height, $similarity, $checked) = $item;
    if (in_array($group, $bad_group)) {
        unset($arr[$key]);
        $b++;
    }
}
array_to_csv('f:\tmp\coloring_all_cleaned_tmp.csv', $arr, false, 0, 'w', ',');

$db_name = 'image_index';
$t_name = 'image_doubles';
dbquery("SELECT COUNT(*) FROM `$db_name`.`$t_name`;");
$i = 0;
$group_trigger = 1; //Номер группы, устанавливаю 1 для удобства 1го обхода
$g = 0; //Итератор группы, номер файла
foreach ($arr as $item) {
    $i++;
    list($group, $full_path, $size, $date, $width, $height, $similarity, $checked) = $item;
    $new_name = basename($full_path);
    if (($res = dbquery("SELECT `id`, `old_name` FROM `$db_name`.`$t_name` WHERE `id` > 247726 AND `new_name` = '$new_name';", 1)) !== FALSE) {
        $res = $res[0];

        if ($group_trigger == $group && @$g == 0) {
            $parent_id = $res[0];
            $g = 1;
        } else if ($group_trigger == $group && @$g > 0) {
            dbquery("UPDATE `$db_name`.`$t_name` SET `new_name` = '', `size` = 0, `width` = 0, `height` = 0, `parent_id` = $parent_id WHERE `id` = $res[0];");
            unlink($full_path);
            $g++;
        } else if ($group_trigger < $group) {
            $parent_id = $res[0];
            $group_trigger = $group;
            $g = 1;
        }

//        rename($full_path, 'f:\Dumps\google_images\coloring\import\\' . $res[1]);
//        dbquery("DELETE FROM `$db_name`.`$t_name` WHERE `id` = $res[0];");
    }
    $i % 100 == 0 ? echo_time_wasted($i) : '';
}
array_to_csv('f:\tmp\coloring_all_cleaned.csv', $arr, false, 0, 'w', ',');
//endregion


//region MIX & MASH DB
$db_name = 'image_index';
$db_name = 'mfa_humanbody14.com';
$t_name = 'image_doubles';

$q1 = "SELECT `ID` FROM `$db_name`.`wp_posts` WHERE `post_type` = 'post' ORDER BY `ID` DESC;"; // SHUFFLLE OLD TO NEW random POST ID
$res = dbquery($q1);

foreach ($res as $item) {
    $tmp[] = $item['ID'];
}

$tmp2 = $tmp;
asort($tmp2);
for ($i = 0; $i < 100; $i++) {
    shuffle($tmp2);
}

$i = 0;
foreach ($tmp as $key => $item) {
    $i++;
    $tmp3[$i]['old'] = $item;
    $tmp3[$i]['new'] = $tmp2[$key];
}
//UPDATES
$i = 0;
foreach ($tmp3 as $item) {
    $tmp = $item['old'] + 1000000;
    $tmp2 = $item['new'] + 1000000;

    $q[] = "UPDATE `$db_name`.`wp_posts` SET `ID` = $tmp WHERE `ID` = $item[old];";
    $q[] = "UPDATE `$db_name`.`wp_posts` SET `ID` = $tmp2 WHERE `ID` = $item[new];";

    $q[] = "UPDATE `$db_name`.`wp_posts` SET `ID` = $item[old] WHERE `ID` = $tmp2;";
    $q[] = "UPDATE `$db_name`.`wp_posts` SET `ID` = $item[new] WHERE `ID` = $tmp;";

    $q[] = "UPDATE `$db_name`.`wp_term_relationships` SET `object_id` = $tmp WHERE `object_id` = $item[old];";
    $q[] = "UPDATE `$db_name`.`wp_term_relationships` SET `object_id` = $tmp2 WHERE `object_id` = $item[new];";

    $q[] = "UPDATE `$db_name`.`wp_term_relationships` SET `object_id` = $item[old] WHERE `object_id` = $tmp2;";
    $q[] = "UPDATE `$db_name`.`wp_term_relationships` SET `object_id` = $item[new] WHERE `object_id` = $tmp;";

    $q[] = "UPDATE `$db_name`.`wp_posts` SET `post_parent` = $item[new] WHERE `post_parent` = $item[old] AND `post_type` = 'attachment';"; // Это новые ID постов сюда пойдут

    dbquery($q);
    unset($q);

    $i++;
    $i % 1000 == 0 ? echo_time_wasted($i) : '';
}
//endregion

dbquery("SELECT COUNT(*) FROM `$db_name`.`$t_name`;");

$query = "SELECT `id`, `new_name`, `parent_id` FROM `$db_name`.`$t_name` WHERE `theme` = $theme;";
$i = 0;
if ($result = mysqli_query($link, $query)) {

    /* извлечение ассоциативного массива */
    while ($row = mysqli_fetch_assoc($result)) {
        $i++;
        if ($row['new_name'] == FALSE) {
            $tmp = dbquery("SELECT `new_name`,`parent_id` FROM `$db_name`.`$t_name` WHERE `id` = $row[parent_id];");
            $tmp = $tmp[0];
            if ($tmp['new_name'] == FALSE) {
                @$f++;
                if (($tmp2 = dbquery("SELECT `new_name` FROM `$db_name`.`$t_name` WHERE `id` = $tmp[parent_id]")) !== FALSE) {
                    @$s++;
                    dbquery("UPDATE `$db_name`.`$t_name` SET `parent_id` = $tmp[parent_id] WHERE `id` = $row[id];");
                }
            }
        }
        $i % 5000 == 0 ? echo_time_wasted($i, " $f  |  $s ") : '';
    }
}

$tmp = unserialize(file_get_contents(__DIR__ . '/debug_data/badimgs.txt'));
foreach ($tmp as $item) {

}
###############
$tmp = file('F:/tmp/99pins_urls2.txt', FILE_IGNORE_NEW_LINES);
$rand_host = array('pinnet.club', 'toppins.club', 'bestpins.club', '99pins.site');
foreach ($tmp as $item) {
    $tmp2 = parse_url($item);
    shuffle($rand_host);
    $tmp2['scheme'] = 'http://';
    $tmp2['host'] = $rand_host[0];
    $tmp2[] = PHP_EOL;
    $fin[] = implode('', $tmp2);
}
file_put_contents('F:/tmp/99pins_redir.txt', $fin);
##############

$tmp = is_image('f:\Dumps\downloaded sites\anatomywrap.com\wp-content\uploads\2018\04\pt4\label-female-reproductive-system-female-reproductive-system-simple-diagram-human-anatomy-diagram.jpg');
//BAD FILES
$dir = 'f:\Dumps\downloaded sites\anatomywrap.com\wp-content\uploads\2018\04\pt4\double/';
$files = scandir($dir);
$i = 0;
foreach ($files as $item) {
    if (preg_match('/[0-9]+x[0-9]+/i', $item)) {
        rename($dir . '/' . $item, $dir . '/crop/' . $item);
    }
}
foreach ($files as $item) {
    $i++;
    $fp = $dir . '/' . $item;
    if (is_file($fp)) {
        $tmp = hash_file('md5', $fp);
        $res[$tmp] += 1;
    }
    if ($i % 100 == 0) {
        echo_time_wasted($i);
    }
}
arsort($res);

//TEST FILES
$dir = 'f:\Dumps\downloaded sites\anatomywrap.com\wp-content\uploads\2018\04\pt1\clean/';
$files = scandir($dir);
$i = 0;
foreach ($files as $item) {
    $i++;
    $fp = $dir . '/' . $item;
    if (is_file($fp)) {
        $tmp = hash_file('md5', $fp);
        if (key_exists($tmp, $res)) {
            copy($fp, $dir . '/bad/' . $item);
            $z++;
        }
    }
    if ($i % 100 == 0) {
        echo_time_wasted($i, $z);
    }
}

$tmp = hash_file('md5', $source_file);
//region Гистограмма с дополнением моим
// histogram options

$maxheight = 300;
$barwidth = 2;

$im = ImageCreateFromJpeg($source_file);

$imgw = imagesx($im);
$imgh = imagesy($im);

// n = total number or pixels

$n = $imgw * $imgh;

$histo = array();

for ($i = 0; $i < $imgw; $i++) {
    for ($j = 0; $j < $imgh; $j++) {

        // get the rgb value for current pixel

        $rgb = ImageColorAt($im, $i, $j);

        // extract each value for r, g, b

        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        // get the Value from the RGB value

        $V = round(($r + $g + $b) / 3);

        // add the point to the histogram

        $histo[$V] += $V / $n;

    }
}
//$krsort = $histo;
//krsort($krsort);
//$arsort = $histo;
//arsort($arsort);
// find the maximum in the histogram in order to display a normated graph

$max = 0;
for ($i = 0; $i < 255; $i++) {
    if ($histo[$i] > $max) {
        $max = $histo[$i];
    }
}

echo "<div style='width: " . (256 * $barwidth) . "px; border: 1px solid'>";
for ($i = 0; $i < 255; $i++) {
    $val += $histo[$i];

    $h = ($histo[$i] / $max) * $maxheight;

    echo "<img src=\"img.gif\" width=\"" . $barwidth . "\"
height=\"" . $h . "\" border=\"0\">";
}
echo "</div>";
//endregion