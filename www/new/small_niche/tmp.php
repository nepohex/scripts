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
$tmp = file('F:/tmp/body_facts.txt', FILE_IGNORE_NEW_LINES);
$tmp = array_unique($tmp);
foreach ($tmp as $key => $item) {
    if (count_strlen_html($item) < 30) {
        unset($tmp[$key]);
    } else {
        if (striposa($item, array('getty', 'istock', 'shutterstock','photograph:'))) {
            unset($tmp[$key]);
        }
    }
}
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