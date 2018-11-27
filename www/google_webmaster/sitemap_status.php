<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 024.24.11.2018
 * Time: 23:51
 *
 * SELECT `t2`.`domain`,`t1`.* FROM `sitemap` AS `t1`
 * JOIN `site` AS `t2` ON `t1`.`site_id` = `t2`.`id`
 * WHERE `map_type` = 'image';
 */
//include('../new/includes/functions.php');
require('../../vendor/autoload.php'); //home
//require('../serverside/vendor/autoload.php'); //server
require('../serverside/includes/Functions.php');
require ('./debug/dbconf.php');

$Functions = new Functions();

$Functions->mysqli_connect2($db['name'], $db['host'], $db['usr'], $db['pwd']);

//auth
$client = new Google_Client();
$client->setAuthConfig('auth/mfaconsole-b2354dff8c88.json');
$client->addScope(Google_Service_Webmasters::WEBMASTERS_READONLY);
// Your redirect URI can be any registered URI, but in this example
// we redirect back to this same page
$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
$client->setRedirectUri($redirect_uri);
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);
}

//list sites
$service = new Google_Service_Webmasters($client);
$request = new Google_Service_Webmasters_SitesListResponse();
$request->getSiteEntry();
$listSites = $service->sites->listSites();
// Получаем в формате URL сайтов https://siteurl.ru/
$tmps = $listSites['siteEntry'];

if (!is_array($tmps)) {
    $Functions->echo2("Cant get sites list!");
    exit();
} else {
    $Functions->echo2("Выгрузили из консоли " . count($tmps) . " сайтов, проверяем доступы");
    foreach ($tmps as $site) {
        if ($site['permissionLevel'] != 'siteUnverifiedUser') {
            $sites[] = $site['siteUrl'];
        }
    }
    $Functions->echo2("Итого сайтов с доступом для выгрузки " . count($sites));
}
//echo2(print_r2($sites));

$i = 0;
$z = 0;
$f = 0;
foreach ($sites as $site) {
    $site_id = get_site_id($site);
    $request = $service->sitemaps->listSitemaps($site);
    if (is_array($request['sitemap']) && @count($request['sitemap']) > 0) {
        foreach ($request['sitemap'] as $sitemap) {
            if (today_done($sitemap, $site_id) == FALSE) {
                //Return TRUE on good map / FALSE on error sitemap URL
                if (insert_sitemap_info($sitemap, $site_id) == TRUE) {
                    $i++; //good map
                } else {
                    $f++; //fail url
                }
            } else {
                $z++;
            }
        }
    }
}
$Functions->echo2("Добавлено данных для $i Sitemaps, $f карт сайта добавлены в консоль с ошибками (неверный урл). $z уже было сегодня обновлено, поэтому их не загружали.");

function today_done($sitemap_arr, $site_id)
{
    global $Functions;

    $path = parse_url($sitemap_arr['path'], PHP_URL_PATH);

    if ($Functions->dbquery("SELECT `id` FROM `sitemap` WHERE `site_id` = $site_id AND `date_grabbed` = DATE(NOW()) AND `sitemap_path` = '$path';")) {
        return TRUE;
    } else {
        return FALSE;
    }
}

function insert_sitemap_info($sitemap_arr, $site_id)
{
    global $Functions;

    $path = parse_url($sitemap_arr['path'], PHP_URL_PATH);
    $date = date_create($sitemap_arr['lastDownloaded']);
    $date = date_format($date, 'Y-m-d H:i:s');

    //boolean to numbers
    boolval($sitemap_arr['isPending']) ? $isPending = 1 : $isPending = 0;
    boolval($sitemap_arr['isSitemapsIndex']) ? $isSitemapsIndex = 1 : $isSitemapsIndex = 0;
    if ($sitemap_arr['type'] === null) { //Если неправильный урл, то type = null будет
        $error_url = 'error_url';
    }

    //Внутри каждой Sitemap есть(могут быть) 2 раздела в ['contents']
    //Почему-то даже если нету вообще $sitemap_arr['contents'] , то is_array возвращает TRUE.
    if (is_array($sitemap_arr['contents']) && @count($sitemap_arr['contents']) > 0) {
        foreach ($sitemap_arr['contents'] as $sitemap_type) {
            $Functions->dbquery("INSERT INTO `sitemap` (`site_id`,`map_type`,`sitemap_path`,`map_urls_indexed`,`map_urls_submitted`,`g_ispending`,`g_issitemapsindex`,`g_lastdownloaded`,`g_errors`,`g_warnings`,`date_grabbed`) VALUES
 ('$site_id','$sitemap_type[type]','$path',
 '$sitemap_type[indexed]','$sitemap_type[submitted]',$isPending,
 $isSitemapsIndex,'$date','$sitemap_arr[errors]','$sitemap_arr[warnings]',DATE(NOW()));");
        }
        return TRUE;
    } else {
        //Если по карте еще нет никаких данных ,например она только добавлена
        $Functions->dbquery("INSERT INTO `sitemap` (`site_id`,`map_type`,`sitemap_path`,`map_urls_indexed`,`map_urls_submitted`,`g_ispending`,`g_issitemapsindex`,`g_lastdownloaded`,`g_errors`,`g_warnings`,`date_grabbed`) VALUES
 ('$site_id','$error_url','$path',
 '0','0',$isPending,
 $isSitemapsIndex,'$date','$sitemap_arr[errors]','$sitemap_arr[warnings]',DATE(NOW()));");
        return FALSE;
    }
}

function get_site_id($site)
{
    global $Functions;
    $url = parse_url($site);
    if ($site_id = $Functions->dbquery("SELECT `id` FROM `site` WHERE `domain` = '$url[host]';")) {
    } else {
        if ($url['scheme'] == 'http') {
            $scheme = 0;
        } else {
            $scheme = 1;
        }
        $site_id = $Functions->dbquery("INSERT INTO `site` (`siteurl`,`domain`,`https`) VALUES ('$site','$url[host]',$scheme);", 0, 0, 0, 0, 0, 1);
    }
    return $site_id;
}