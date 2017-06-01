<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 12.05.2017
 * Time: 19:54
 * Нужно сначала пройти авторизацию через Google API, скачать JSON, далее получить адрес почты и дать ему доступ к сайтам в Google Webmaster
 */
include('../new/includes/functions.php');
require('../../vendor/autoload.php');
$fp_log = "log.txt";
$double_log = 1;

if ($argv[1] == false) {
    $prev_days_to_parse = 14;
} else {
    $prev_days_to_parse = $argv[1];
}

if ($argv[2] == false) {
    $iterations = 50; //Сколько запросов делать с самыми популярными словами из первой выгрузки. Из 5000 набирается 1000 слов.
} else {
    $iterations = $argv[2];
}
echo2("Starting to parse all domains for keys in Google Webmaster for last $prev_days_to_parse days, Parse $iterations keys if >4950 keys found.");

$db_usr = 'root';
$import_db = 'image_index';
$import_table = 'semrush_keys';
mysqli_connect2($import_db);
$import_keys = dbquery("SELECT COUNT(*) FROM `$import_db`.`$import_table`;");
echo2("Было в таблице $import_table записей с ключами = $import_keys");

// убираем сезонные запросы, с ними итак все понятно
$replace2 = array('2017', '2016', '2014', '2013', '2015');

$time = time() - ($prev_days_to_parse * 24 * 60 * 60);
$minumum_date = date('Y-m-d', $time);
$current_date = $minumum_date;

//$sites = array('netvoine.info');

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

$service = new Google_Service_Webmasters($client);
$request = new Google_Service_Webmasters_SitesListResponse();
$request->getSiteEntry();
$result = $service->sites->listSites();

$tmps = $result['modelData']['siteEntry'];
foreach ($tmps as $site) {
    if ($site['permissionLevel'] != 'siteUnverifiedUser') {
        $sites[] = $site['siteUrl'];
    }
}
echo2("Выгрузили " . count($sites) . " сайтов, начинаем собирать по ним инфу");
print_r2($sites);

foreach ($sites as $site) {
    $t = 0;
    $current_date = $minumum_date;
    while ($t < $prev_days_to_parse - 2) {
//Пример 1 - получение 5000 ключей с их статистикой по поиску по картинкам
        $service = new Google_Service_Webmasters($client);
        $request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
        $request->setStartDate($current_date);
        $request->setEndDate($current_date);
        $request->setDimensions(array('query'));
        $request->setSearchType('image');
        $request->setRowLimit('5000');
        $result = $service->searchanalytics->query($site, $request);

        $data = $result['modelData']['rows'];
        sleep(1);
        if (count($data) > 4950) {
            echo2("#$current_date Для сайта $site найдено более 4950 ключей, начинаем глубокую выгрузку");
            foreach ($data as $item) {
                $tmp = $item['keys'][0];
                if (ctype_alnum(str_replace(' ', '', $tmp))) {
                    $valid_keys_arr[] = $item;
                    $valid_keys[] = $item['keys'][0];
                    $tmp = explode(" ", $item['keys'][0]);
                    foreach ($tmp as $word) {
                        $words_used[$word] += 1;
                    }
                }
            }
            echo2("Всего получили " . count($data) . " ключевых фраз из Google, из них прошли по языку " . count($valid_keys_arr) . " и получили массив с использованными словами в размере " . count($words_used));
            echo2("Начинаем выгружать запросы содержащие топ $iterations самых популярных слов");
            arsort($words_used);
            for ($i = 0; $i < $iterations; $i++) {
                $word = key($words_used);
                next($words_used);
                $service = new Google_Service_Webmasters($client);
                $request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
                $request->setStartDate($current_date);
                $request->setEndDate($current_date);
                $request->setDimensions(array('query'));
                $request->setSearchType('image');
                $request->setRowLimit('5000');
                $result = $service->searchanalytics->query($site, $request);
                $filtri = new Google_Service_Webmasters_ApiDimensionFilterGroup;
                $filtro = new Google_Service_Webmasters_ApiDimensionFilter;
                $filtro->setDimension("query");
                $filtro->setOperator("contains");
                $filtro->setExpression($word);
                $filtri->setFilters(array($filtro));
                $request->setDimensionFilterGroups(array($filtri));
                $result = $service->searchanalytics->query($site, $request);
                $data_words[] = $result['modelData']['rows'];
                //echo2("Выгрузили данные по ключу $word в количестве " . count($result['modelData']['rows']));
            }
            foreach ($data_words as $data) {
                foreach ($data as $item) {
                    $tmp = $item['keys'][0];
                    if (ctype_alnum(str_replace(' ', '', $tmp))) {
                        $valid_keys_arr[] = $item;
                        $valid_keys[] = $item['keys'][0];
                        $tmp = explode(" ", $item['keys'][0]);
                        foreach ($tmp as $word) {
                            $words_used[$word] += 1;
                        }
                    }
                }
            }
            arsort($words_used);
            array_unique($valid_keys);
            echo2("После $iterations слили все данные и получили только уникальные фразы в размере " . count($valid_keys));
//            file_put_contents("tmp_google_keys.txt", serialize($words_used));
            unset ($data_words, $valid_keys_arr, $words_used);
        }
        $t++;
        $current_date = date('Y-m-d', strtotime($minumum_date) + $t * 24 * 60 * 60);
    }
    if ($valid_keys) {
        echo2("Для сайта $site получили всего " . count($valid_keys) . " уникальных ключей за $t дней и $iterations в каждом");
        $fin[$site] = $valid_keys;
        foreach ($valid_keys as $item) {
            $item = trim(str_replace('  ', ' ', str_replace($replace2, ' ', $item)));
            $queries[] = "INSERT INTO `$import_db`.`$import_table` (`key_id`, `key`, `adwords`, `results`) VALUES (NULL, '$item', '', '');";
            $valid++;
        }
        dbquery($queries, null, null, null, true);
        file_put_contents("tmp_google_valid_queries.txt", serialize($fin));
        $tmp = dbquery("SELECT COUNT(*) FROM `$import_db`.`$import_table`;");
        $new_keys = $tmp - $import_keys;
        echo2("Влилось в базу ключей всего за весь прогон $new_keys . $valid ключей было отправлено на вставку с сайта $site. Было изначально ключей $import_keys.");
        echo_time_wasted();
        unset($valid_keys, $valid, $queries);
    }
}