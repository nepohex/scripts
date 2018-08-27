<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 12.05.2017
 * Time: 19:54
 * Нужно сначала пройти авторизацию через Google API, скачать JSON, далее получить адрес почты и дать ему доступ к сайтам в Google Webmaster
 * адовый говнокод, надо сделать по-человечески
 * также пока в режиме дебуга, по одному домену
 * UPD! Более не актуально! Максимум можно выгрузить только 5000 запросов за сутки.
 */
include('../new/includes/functions.php');
require('../../vendor/autoload.php');
$fp_log = "log.txt";
$double_log = 1;

if ($argv[1] == false) {
    $prev_days_to_parse = 40;
} else {
    $prev_days_to_parse = $argv[1];
}

//UPD! Более не актуально! Максимум можно выгрузить только 5000 запросов за сутки. Ставим 1
if ($argv[2] == false) {
    $iterations = 1; //Сколько запросов делать с самыми популярными словами из первой выгрузки. Из 5000 набирается 1000 слов.
} else {
    $iterations = $argv[2];
}
echo2("Starting to parse all domains for keys in Google Webmaster for last $prev_days_to_parse days, Parse $iterations keys if >4950 keys found.");

$db_usr = 'root';
$import_db = 'image_index';
$import_table = 'google_webmaster';
mysqli_connect2($import_db);
$import_keys = dbquery("SELECT COUNT(*) FROM `$import_db`.`$import_table`;");
echo2("Было в таблице $import_table записей с ключами = $import_keys");

// убираем сезонные запросы, с ними итак все понятно
$replace2 = array('2017', '2016', '2014', '2013', '2015');
$replace2 = array('gdfgdfjgh'); //чтобы не удалять год из ключей

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

//$tmps = $result['modelData']['siteEntry']; //old
$tmps = $result['siteEntry'];
if (!is_array($tmps)) {
    echo2("Cant get sites list!");
} else {
    echo2("Выгрузили из консоли " . count($tmps) . " сайтов, проверяем доступы");
    foreach ($tmps as $site) {
        if ($site['permissionLevel'] != 'siteUnverifiedUser') {
            $sites[] = $site['siteUrl'];
        }
    }
    echo2("Итого сайтов с доступом для выгрузки " . count($sites));
}
echo2(print_r2($sites));

foreach ($sites as $site) {
    $site = 'https://medwrite.biz'; //debug
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

        $data = $result['rows'];
//        usleep(500000);
        sleep(1);
        if (count($data) > 4950) {
            echo2("#$current_date Для сайта $site найдено более 4950 ключей, начинаем глубокую выгрузку");
            foreach ($data as $item) {
                $tmp = $item['keys'][0];
                if (ctype_alnum(str_replace(' ', '', $tmp))) {
                    @$c_valid++;
                    $item['date'] = $current_date;
                    $valid_keys_arr[] = $item;
                    $valid_keys[] = $item['keys'][0];
                    $tmp = explode(" ", $item['keys'][0]);
                    foreach ($tmp as $word) {
                        $words_used[$word] += 1;
                    }
                }
            }
            echo2("Всего получили " . count($data) . " ключевых фраз из Google, из них прошли по языку $c_valid и получили массив с использованными словами в размере " . count($words_used));
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
//                $result = $service->searchanalytics->query($site, $request);
                $filtri = new Google_Service_Webmasters_ApiDimensionFilterGroup;
                $filtro = new Google_Service_Webmasters_ApiDimensionFilter;
                $filtro->setDimension("query");
                $filtro->setOperator("contains");
                $filtro->setExpression($word);

//                $filtr2 = new Google_Service_Webmasters_ApiDimensionFilter;
//                $filtr2->setDimension("country");
//                $filtr2->setOperator("equals");
//                $filtr2->setExpression("IDN");
//                $filtri->setFilters(array($filtro, $filtr2));

                $filtri->setFilters(array($filtro));
                $request->setDimensionFilterGroups(array($filtri));
                $result = $service->searchanalytics->query($site, $request);
                $data_words[] = $result['rows'];
                $i % 10 == 0 ? echo_time_wasted($i) : '';
                //echo2("Выгрузили данные по ключу $word в количестве " . count($result['modelData']['rows']));
            }
            foreach ($data_words as $data) {
                foreach ($data as $item) {
                    $tmp = $item['keys'][0];
                    if (ctype_alnum(str_replace(' ', '', $tmp))) {
                        $item['date'] = $current_date;
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
            $valid_keys = array_unique($valid_keys);
            if (!$debug[$site]['total_valid_keys']) {
                $debug[$site]['total_valid_keys'] = array();
            }
            $debug[$site]['site'] = $site;
            $debug[$site][$current_date]['valid'] = count($valid_keys);
            $debug[$site]['total_valid_keys'] = array_merge($debug[$site]['total_valid_keys'], $valid_keys);
            $debug[$site][$current_date]['total_valid'] = count($debug[$site]['total_valid_keys']);

            echo2("После $iterations итераций слили все данные и получили только уникальные фразы в размере " . count($debug[$site]['total_valid_keys']));
//            file_put_contents("tmp_google_keys.txt", serialize($words_used));
//            unset ($data_words, $valid_keys_arr, $words_used);
            unset ($data_words, $words_used, $c_valid);
            echo_time_wasted();

            if ($valid_keys) {
//                echo2("Для сайта $site получили всего " . count($valid_keys) . " уникальных ключей за $t дней и по $iterations итераций на каждый день");
//                $fin[$site] = $valid_keys;
                foreach ($valid_keys_arr as $item) {
                    $key = trim(str_replace('  ', ' ', str_replace($replace2, ' ', $item['keys'][0])));
                    //Массив с датой как ключ, чтобы не заливать один и тот же ключ в базу для одного и того же дня.
                    if (!isset($loaded_keys[$item['date']])) {
                        $loaded_keys[$item['date']] = array();
                    }
                    if (!in_array($key, $loaded_keys[$item['date']])) {
                        $position = round($item['position']);
                        $queries[] = "INSERT INTO `$import_db`.`$import_table` (`key_id`, `key`, `clicks`, `impressions`, `position`, `date`) VALUES (NULL, '$key', $item[clicks], $item[impressions],$position,'$item[date]' );";
                        $loaded_keys[$item['date']][] = $key;
                        $valid++;
                    }
                }
                dbquery($queries, null, null, null, true);
//        file_put_contents("tmp_google_valid_queries.txt", serialize($fin));
//                $tmp = dbquery("SELECT COUNT(*) FROM `$import_db`.`$import_table`;");
//                $new_keys = $tmp - $import_keys;
//        echo2(" === > Влилось в базу ключей всего за весь прогон $new_keys . $valid ключей было отправлено на вставку с сайта $site. Было изначально ключей $import_keys.");
//        echo2(" === > !Заливаем все ключи подряд (без проверки на дубли) - Временно!");
                unset($valid_keys, $valid, $queries, $fin, $valid_keys_arr, $loaded_keys);
            }
        } else {
            echo2("#$current_date - $site - меньше 4950 ключей - пропускаем день, идем дальше!");
        }
        $t++;
        $current_date = date('Y-m-d', strtotime($minumum_date) + $t * 24 * 60 * 60);
    }
    echo2("Выгрузили сайт $site");
    exit(); // debug
}