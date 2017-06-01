<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 29.05.2017
 * Time: 1:00
 * Обновить для сайтов Pinterest новые коды в базу данных (например Виджет + коды Adsense).
 * Массив Black list sites обязателен, может быть пустым -> не будет обновлять базы данных этих доменов.
 * Если есть массив White list sites -> будет обновлять только эти сайты. Можно закомментировать если не нужно.
 */
if ($fp = mysqli_connect("localhost", "root", '')) {
    echo "MYSQL connect SUCCESS!" . PHP_EOL;
} else {
    echo "MYSQL CONNECT FAIL! CHECK DATA!" . PHP_EOL;
    exit();
}
//Без WWW и прочего, только домен. Массив обязателен, может быть пустым.
$black_list_sites = array('23aprel.org', 'highlighted-hair.com', 'eventshairstyles.xyz', 'dreadlocks2017.xyz', 'bun-hairstyles.com', 'graduatedhairstyles.info', 'waterfallhair.xyz', 'platinumhair.info', 'sidepartedhairstyles.men', 'punkhairstyles.us', 'quiffhaircut.xyz', 'hipsterhair.xyz', 'mileycyrushair.site', 'glasses-haircuts.info');
//Comment White List if don't needed.
$white_list_sites = array('acaixadaestefi.com', 'ap903.com', 'artesanatoscriaredecorar.com', 'imagenesdeamorparadibujar.net', 'lifebylyons.com', 'pokabar.com', 'reveriesupplyjournal.com', 'unleashedbymc.com', 'wildflowerroyalty.com', 'elolpicture.com', 'somedayatatime.com', 'keepinitboy.com', 'detodounpocoyalgomas.com');

$result = mysqli_query($fp, "SHOW DATABASES;");
while ($tmp = mysqli_fetch_row($result)) {
    $dbs[] = $tmp[0];
};
var_dump($dbs);
foreach ($dbs as $db_name) {
    $result = mysqli_query($fp, "SELECT `option_value` FROM `$db_name`.`wp_options` WHERE `option_name` = 'siteurl';");
    if ($result) {
        $sitename = mysqli_fetch_row($result);
        if ((!in_array(parse_url($sitename[0], PHP_URL_HOST), $black_list_sites)) && ((in_array(parse_url($sitename[0], PHP_URL_HOST), $white_list_sites)) || count($white_list_sites) == 0)) {
            echo "Fixing DB $db_name , site $sitename[0]" . PHP_EOL;
            $data1 = file_get_contents("widget_execphp.txt");
            // WP понимает только \n в качестве переноса строк
            $data1 = str_replace("\r\n", "\n", $data1);
            if ($data1 != true) {
                echo "Error - didnt get data for insert from file!";
                exit();
            }
            update_wp_db($fp, $db_name, 'wp_options', 'option_value', $data1, 'option_name', 'widget_execphp');

            $data2 = file_get_contents("semiwallpaper.txt");
            $data2 = str_replace("\r\n", "\n", $data2);
            if ($data2 != true) {
                echo "Error - didnt get data for insert from file!";
                exit();
            }
            update_wp_db($fp, $db_name, 'wp_options', 'option_value', $data2, 'option_name', 'semiwallpaper');
            // exit(); //DEBUG 1 DB
        } else {
            if ($white_list_sites) {
                echo "$sitename[0] not in white list! Ignore!" . PHP_EOL;
            } else {
                echo "$sitename[0] in black list! Ignore!" . PHP_EOL;
            }
        }
    } else {
        echo "No `option_name` siteurl in db `$db_name`, skip." . PHP_EOL;
    }
}

/**
 * "UPDATE `$db_name`.`$db_table` SET `$set_column` = '$set_value' WHERE `$condition_column` = `$condition_value`;";
 * @param $mysql_resource mysqli ресурс связи с MYSQL
 * @param $db_name
 * @param $db_table
 * @param $set_column string название столбца в который будем вставлять данные
 * @param $set_value string что вставлять в строку
 * @param $condition_column string название столбца в который будем вставлять
 * @param $condition_value string содержимое строки условия
 */
function update_wp_db($mysql_resource, $db_name, $db_table, $set_column, $set_value, $condition_column, $condition_value)
{
    $set_value = mysqli_escape_string($mysql_resource, $set_value);
    mysqli_query($mysql_resource, "START TRANSACTION;");
    $query = "UPDATE `$db_name`.`$db_table` SET `$set_column` = '$set_value' WHERE `$condition_column` = '$condition_value';";
    $z = mysqli_query($mysql_resource, $query);
    $rows = mysqli_affected_rows($mysql_resource);
    if ($rows < 0) {
        echo "Transaction failed, 0 rows affected, rolling back! Check query: " . PHP_EOL . $query . PHP_EOL;
        if (mysqli_error($mysql_resource)) {
            echo mysqli_error($mysql_resource);
        }
        $z = mysqli_query($mysql_resource, "ROLLBACK;");
    } else {
        mysqli_query($mysql_resource, "COMMIT;");
        echo "TRANSACTION SUCCESSFUL, no errors. $rows ROWS AFFECTED! Committed! If 0 rows -> means could be updated before and dont need changes." . PHP_EOL;
    }
}