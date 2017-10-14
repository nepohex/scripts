<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 03.12.2016
 * Time: 1:23
 * Не получается выводить статус этого файла т.к. путь лога создается позже чем идет вывод результатов
 */
include "multiconf.php";
if ($multi_int_mode) {
    change_collation('utf8mb4', 'utf8mb4_unicode_ci', $db_name);
    echo2("MULTI_INT_MODE = TRUE , меняем db collation каждой таблицы и базы данных на мультиязычную (utf8mb4_unicode_ci)");
    next_script();
}
foreach ($project_dirs as $dir) {
    mkdir2($dir, 1);
}
$pwd_log = fopen(__DIR__ . '/passwords_log.txt', "a");
$installer_log = fopen(__DIR__ . '/installer_command.txt', "a");

echo2("Начинаем выполнять скрипт " . $_SERVER['SCRIPT_FILENAME']);

//Генерим данные для хостинга-конфига
mkdir2($work_dir . '/wp-content');
gen_wp_db_conf($site_name, $installer['db_prefix'], $keyword);

$tmp = file_get_contents('includes/' . $wp_conf_tpl);
//file_put_contents($work_dir . '/wp-config.php', '<?php' . PHP_EOL . 'define (\'WPCACHEHOME\', \'' . $installer['wpcachepluginpath'] . '\');' . PHP_EOL . 'define(\'DB_NAME\', \'' . $wp_conf_db_name . '\');' . PHP_EOL . 'define(\'DB_USER\', \'' . $wp_conf_db_usr . '\');' . PHP_EOL . 'define(\'DB_PASSWORD\', \'' . $wp_conf_db_pwd . '\');' . PHP_EOL . $tmp);
file_put_contents($work_dir . '/wp-config.php', '<?php' . PHP_EOL . 'define (\'WPCACHEHOME\', ABSPATH.\'wp-content/plugins/wp-super-cache/\');' . PHP_EOL . 'define(\'DB_NAME\', \'' . $wp_conf_db_name . '\');' . PHP_EOL . 'define(\'DB_USER\', \'' . $wp_conf_db_usr . '\');' . PHP_EOL . 'define(\'DB_PASSWORD\', \'' . $wp_conf_db_pwd . '\');' . PHP_EOL . $tmp);
echo2("Сгенерили wp-config для нового сайта, db_name , db_user , db_pwd : $wp_conf_db_name $wp_conf_db_usr $wp_conf_db_pwd");

$tmp = file_get_contents('includes/' . $wp_conf_cache_tpl);
//file_put_contents($work_dir . '/wp-content/wp-cache-config.php', '<?php' . PHP_EOL . '$cache_path = \'' . $installer['cache_dir'] . '\';' . PHP_EOL . $tmp);
file_put_contents($work_dir . '/wp-content/wp-cache-config.php', '<?php' . PHP_EOL . '$cache_path = ABSPATH.\'wp-content/cache/\';' . PHP_EOL . $tmp);

copy(__DIR__ . '/includes/wp_instance_files_db.zip', $work_dir . '/site.zip');

gen_installer(__DIR__ . '/includes/installer_instance.txt', $work_dir . '/installer.php', $installer['db_host'], $installer['db_usr'], $installer['db_pwd'], $wp_conf_db_name, $wp_conf_db_usr, $wp_conf_db_pwd, 'dump.sql');
echo2("Записываем команду инсталлера в лог __DIR__.'/installer_command.txt");
fwrite($installer_log, $installer['command'] . PHP_EOL);
echo2($installer['command']);
// Закончили

import_db_instance();

next_script();

function import_db_instance()
{

    global $db_usr;
    global $db_pwd;
    global $db_host;
    global $db_instance;
    global $db_name;
    global $site_url;
    global $blogname;
    global $blogdescription;
    global $default_cat_name;
    global $site_name;
    global $default_cat_slug;
    global $pwd_log;
    global $int_mode;

    $link = mysqli_init();

// Соединяемся с базой 1ый раз, создаем ее
    if (mysqli_real_connect($link, $db_host, $db_usr, $db_pwd, $db_name)) {
        if ($int_mode) {
            change_collation('utf8mb4', 'utf8mb4_unicode_ci', $db_name);
            echo2("INT_MODE = TRUE , меняем db collation каждой таблицы и базы данных на мультиязычную (utf8mb4_unicode_ci)");
        }
        echo2("База уже есть, больше ее не трогаем.");
    } else {
        mysqli_real_connect($link, $db_host, $db_usr, $db_pwd);
        $query = "CREATE DATABASE `" . $db_name . "`"; // Не знаю почему но почему-то выводит ошибку что уже создана база данных, как ни крути
        if (mysqli_query($link, $query)) {
            echo2("Создали базу данных для работы, теперь ее заполняем " . $db_name);
            mysqli_query($link, "USE `" . $db_name . "`;");
            $templine = '';
            $lines = file($db_instance);
            if ($lines == false) {
                echo2("Не можем найти файл с импортом для таблицы, или пустой файл!");
            }
            foreach ($lines as $line) {
                if (substr($line, 0, 2) == '--' || $line == '' || $line == '\n')
                    continue;
                $line = str_replace("\n", "", $line);
                $templine .= $line;
                if (substr(trim($line), -1, 1) == ';') {
                    mysqli_query($link, $templine) or echo2('Ошибка выполнения запроса' . $templine . ': ' . mysqli_error($link));
                    $templine = '';
                }
            }
            $tmp_pwd = pwdgen(14);
            echo2("Пароль для нового сайта $tmp_pwd");
            fwrite($pwd_log, $site_name . PHP_EOL . $tmp_pwd . PHP_EOL);
            $tmp_pwd = md5($tmp_pwd);
            $queries_prepare[] = "UPDATE  `wp_terms` SET  `name` =  '" . $default_cat_name . "', `slug` =  '" . $default_cat_slug . "' WHERE  `wp_terms`.`term_id` =1;";
            $queries_prepare[] = "INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES (NULL, '1', '2016-12-13 17:42:41', '2016-12-13 14:42:41', '[kwayy-sitemap]', 'Sitemap', '', 'publish', 'closed', 'closed', '', 'sitemap', '', '', '2016-12-13 17:42:41', '2016-12-13 14:42:41', '', '0', '" . $site_url . "?page_id=50', '0', 'page', '', '0');";
            $queries_prepare[] = "UPDATE `wp_users` SET `user_pass` ='" . $tmp_pwd . "' , `user_login` = 'wtfowned' WHERE `id` = 1;";
            $queries_prepare[] = 'UPDATE `wp_options` SET `option_value` =\'' . $site_url . '\' WHERE `option_id` = 1 OR `option_id` = 2';
            $queries_prepare[] = "UPDATE `wp_options` SET `option_value` ='" . $blogname . "' WHERE `option_name` = 'blogname';";
            $queries_prepare[] = "UPDATE `wp_options` SET `option_value` ='" . $blogdescription . "' WHERE `option_name` = 'blogdescription';";
            $queries_prepare[] = "UPDATE `wp_options` SET `option_value` ='http://" . $site_name . "/' WHERE `option_name` = 'ossdl_off_cdn_url';";
            $queries_prepare[] = "SELECT `option_value` FROM `wp_options` WHERE `option_name` = 'wpseo'";
            foreach ($queries_prepare as $query_pre) {
                $sqlres = mysqli_query($link, $query_pre);
            }
            $tmp = mysqli_fetch_row($sqlres);
            $ggf = unserialize($tmp[0]);
            $ggf['website_name'] = $blogname;
            $query = "UPDATE `wp_options` SET `option_value` = '" . addslashes(serialize($ggf)) . "' WHERE `option_name` = 'wpseo';";
            mysqli_query($link, $query);
            return echo2("Таблицы в базе данных заполнили!");
        } else {
            echo2("Создать базу данных " . $db_name . " не получилось, либо уже существует " . mysqli_error($link));
            echo2("Чтобы заполнить таблицу, ее нужно удалить и заново запустить скрипт.");
        }
    }
}

function change_collation($charset, $collation, $dbname)
{
    $table_list = dbquery("SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA`='$dbname' AND `TABLE_TYPE`='BASE TABLE'");
    dbquery("ALTER DATABASE `$dbname` CHARACTER SET $charset COLLATE $collation");
    foreach ($table_list as $table) {
        $table = $table['TABLE_NAME'];
        dbquery("ALTER TABLE `$dbname`.`$table` CONVERT TO CHARACTER SET $charset COLLATE $collation;");
    }
}

