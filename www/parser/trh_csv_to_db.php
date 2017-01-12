<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 12.01.2017
 * Time: 19:59
 * Закидываем обработанные данные парсинга сайта trh.com в базу
 */
$start = microtime(true);
$link = mysqli_init();
$image_niches = 'image_trh_niches.txt';
if (!mysqli_real_connect($link, 'localhost', 'root', '', 'hair_spin')) {
    die('Ошибка подключения (' . mysqli_connect_errno() . ') '
        . mysqli_connect_error());
}

function convert($memory_usage)
{
    $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
    return @round($memory_usage / pow(1024, ($i = floor(log($memory_usage, 1024)))), 2) . ' ' . $unit[$i];
}

function echo_time_wasted($i = null)
{
    global $start;
    $time = microtime(true) - $start;
    if ($i) {
        echo3("Идем по строке " . $i . " Скрипт выполняется уже " . number_format($time, 2) . " сек" . " Памяти выделено в пике " . convert(memory_get_peak_usage(true)));
    } else {
        echo3("Скрипт выполняется уже " . number_format($time, 2) . " сек" . " Памяти выделено в пике " . convert(memory_get_peak_usage(true)));
    }

}

function print_r2($val)
{
    echo '<pre>';
    print_r($val);
    echo '</pre>';
    flush();
}

function echo3($str)
{
    echo $str . PHP_EOL;
    flush();
}

function dbquery($queryarr)
{
    global $link;
    if (is_array($queryarr)) {
        foreach ($queryarr as $query) {
            $sqlres = mysqli_query($link, $query);
            if ($error = mysqli_error($link)) {
                echo3("Mysqli error $error в запросе $query");
            }
        }
    } else {
        mysqli_query($link, $queryarr);
        if ($error = mysqli_error($link)) {
            echo3("Mysqli error $error в запросе $queryarr");
        }
    }
    echo3("Запросов в базу прошло " . count($queryarr));
}

$fp = fopen('images_trh_com_clean.csv', 'r');
while ($tmp = fgetcsv($fp, '', ';')) {
    $csv[] = $tmp;
}

echo_time_wasted();

foreach ($csv as $row) {
    $tmp[] = $row[0];
}
$tmp = array_unique($tmp);
foreach ($tmp as $item) {
    $article_urls[] = $item;
}

$queries1 = array();
$i = 1; // ID для картинок
foreach ($csv as $row) {
    $z = 1; // ID для URL в таблице
    foreach ($article_urls as $article_url) {
        if ($row[0] == $article_url) {
            $csv[$i - 1][0] = $z;
            $query1 = "INSERT INTO `urls` (`id`,`url`) VALUES ('" . $z . "','" . $article_url . "');";
// INSERT INTO `hair_spin`.`data` (`id`, `url_id`, `h3`, `text_start`, `img_url`, `img_alt`, `img_source`, `text_template`, `place`, `variants`, `comment`, `avg_len`, `used`) VALUES ('0', '0', 'African American Queen', 'Short hairstyles for black women are killing the hair game lately - this look is leading the pack. The side part of this gorgeous bob sits lower than your average side part for a statement sweep that speaks chic and perfection.', 'http://i1.wp.com/therighthairstyles.com/wp-content/uploads/2013/11/2-black-sideparted-bob.jpg?w=500', 'Black Side-Parted Bob', 'https://www.instagram.com/p/BAQD8fiBjv6/', '', '', '100', '', '300', '0');
            $queries2[] = "INSERT INTO `hair_spin`.`data` (`id`, `url_id`, `h3`, `text_start`, `img_url`, `img_alt`, `img_source`, `text_template`, `place`, `variants`, `comment`, `avg_len`, `used`) VALUES ('$i', '$z', '".addslashes($row[1])."', '".addslashes($row[2])."', '$row[3]', '".addslashes($row[4])."', '$row[5]', '', '', '0', '', '".strlen($row[2])."', '0');";
            if ($query1 !== end($queries1)) {
                $queries1[] = $query1;
            }
            break;
        }
        $z++;
    }
    $tmp = explode(" ", $row[4]);
    foreach ($tmp as $word) {
        $words_used[strtolower($word)] += 1;
    }
    $i++;
}
arsort($words_used);
reset($words_used);
echo3("Посчитали слова, записали результат в файл " . $image_niches);
file_put_contents($image_niches, print_r($words_used, true));
dbquery($queries1);
dbquery($queries2);
echo_time_wasted();


