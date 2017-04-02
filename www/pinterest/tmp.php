<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 22.02.2017
 * Time: 21:41
 */
include('../new/includes/functions.php');
//include ('C:\OpenServer\domains\scripts.loc\vendor\seregazhuk\pinterest-bot\src\Bot.php');
require('../../vendor/autoload.php');
//include('C:\OpenServer\domains\scripts.loc\www\php-whois\src\Phois\Whois\Whois.php');
//use seregazhuk\PinterestBot\Factories\PinterestBot;
header('Content-Type: text/html; charset=utf-8');
$debug_mode = 1;

//Сливание базы с хоста с локальной
//$db_pwd = 'Ma4STVXhTc';
//$db_usr = 'admin_ipzon';
//$db_name = 'admin_pinterest';
//$db_host = '93.170.76.157';
//mysqli_connect2($db_name,$db_host);
//
//$db_insert = 'pin_houzz_dead';
//$db_select = 'pin_houzz_dead';
//dbquery("USE $db_name");
////$query = "SELECT `pin_id`,`domain`,`timestamp` FROM `$db_select` WHERE `status` = 0;";
//$query = "SELECT `id` from `pin_houzz` WHERE `checked` != 0;";
//$insert = dbquery($query, 1);
////dbquery("UPDATE `$db_select` SET `status` = 7;");
//
//$db_pwd = '';
//$db_usr = 'root';
//mysqli_connect2("pinterest");
//
//foreach ($insert as $item) {
//    $query = "UPDATE `pin_houzz` SET `checked` = 1 WHERE `id` = $item";
//        if (dbquery($query, false, 1, false, 1) == 1) {
//        $new++;
//    }
//}
////foreach ($insert as $item) {
////    $query = "INSERT INTO `$db_insert` SET `pin_id` = $item[0], `domain` = '$item[1]' , `timestamp` = '$item[2]'";
////    if (dbquery($query, false, 1, false, 1) == 1) {
////        $new++;
////    }
////}
//echo2($new);
//exit();

$db_pwd = '';
$db_usr = 'root';
mysqli_connect2("pinterest");
$pin_db = 'domains_auc';

//$com = new Com('WScript.shell');
//$com->run('php C:\OpenServer\domains\scripts.loc\www\pinterest\exec.php 1 3 2>&1', 0, false); //2ой параметр положительный чтобы консоль видимой была
//
//$exec = exec('php C:\OpenServer\domains\scripts.loc\www\pinterest\exec.php 10 3 2>&1');
//$exec = exec('php C:\OpenServer\domains\scripts.loc\www\pinterest\exec.php 5 3 2>&1');
//var_dump($output,$array);
//Блок проверки Godaddy доступности > подумать куда перенести.
//$table = 'pin_houzz_top10'; //PIN TOP 10 PARSED
//$query = "SELECT `domain` FROM `$table` WHERE `status` = 1 AND (`7_days_top10_pins_actions` > 100 OR `30_days_top10_pins_actions` > 200) LIMIT 500";
$table = 'pin_houzz_dead';
$count = dbquery("SELECT count(*) FROM `$table` WHERE `status` = 0;");
echo2("Начинаем проверку $count доменов в Godaddy");
$query = "SELECT `domain` FROM `$table` WHERE `status` = 0 LIMIT 500";
$res = dbquery($query, 1);
$proxy = get_proxy();
$fail = 0;

while (count($res) > 0) {
    get_proxy();
    $ch = curl_init();
    $domains = urlencode(implode(',', $res));
    $url = 'https://ru.godaddy.com/domains/actions/dodomainbulksearch.aspx?source=%2fdomains%2fbulk-domain-search.aspx';
    curl_setopt($ch, CURLOPT_URL, $url); // отправляем на
    curl_setopt($ch, CURLOPT_HEADER, 0); // пустые заголовки
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);// таймаут4
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
    if ($proxy_ip) {
        curl_setopt($ch, CURLOPT_PROXY, $proxy_ip);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_login);
    }
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt'); // сохранять куки в файл
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
    curl_setopt($ch, CURLOPT_POST, 1); // использовать данные в post
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'domainNames=' . $domains . '&dotTypes=&extrnl=1&bulk=1&redirectTo=customize');
    $data = curl_exec($ch);
    exec_domain_status($data, $res);
    update_status($unknown, $not_ava, $ava, $table);
    curl_close($ch);
    unlink('cookie.txt');
//    $url = 'https://ru.godaddy.com/domains/actions/json/removedomainsfrompending.aspx?TargetDivID=x&RemoveAll=true';
//    $data = curl_exec($ch);
    unset ($ava, $not_ava, $unknown);
    $res = dbquery($query, 1);
    sleep(120);
}

function exec_domain_status($html, $pack)
{
    global $ava, $not_ava, $unknown, $fail,$proxy_ip;
    preg_match_all('/<input type=\'checkbox\' id=\'(.*)\' name/i', $html, $tmp_ava);
    preg_match_all('/<td align="left" class="t11 OneLinkNoTx"><label for=\'(.*)\' title/i', $html, $tmp_not_ava);
    if (count($tmp_not_ava[1]) < 1) {
        echo2("Скорее всего какая-то проблема с Godaddy, результат запроса сохранен в godaddy_response.txt");
        file_put_contents('godaddy_response.txt', $html);
        file_put_contents('godaddy_pack.txt', serialize($pack));
        echo2 ("$proxy_ip");
        $fail++;
        if ($fail == 10) {
            exit("Уже 10 Fail! Exit!");
        }
    }
    $unknown = array_diff($pack, $tmp_not_ava[1]);
    if (count($unknown) == count($pack)) {
        echo2("100% проблема с Godaddy, результат запроса сохранен в godaddy_response.txt");
        file_put_contents('godaddy_response.txt', $html);
        file_put_contents('godaddy_pack.txt', serialize($pack));
        echo2 ("$proxy_ip");
        exit();
    }
    $ava = $tmp_ava[1];
    $not_ava = array_diff($tmp_not_ava[1], $tmp_ava[1]);
    echo2(count($unknown) . ' / ' . count($ava) . ' / ' . count($not_ava) . " unknown / ava / notava / proxy $proxy_ip");
}

function update_status($unknown, $not_ava, $ava, $table)
{
    if (count($unknown) > 0) {
        foreach ($unknown as $item) {
            dbquery("UPDATE `$table` SET `status` = 8 WHERE `domain` = '$item';");
        }
    }
    if (count($not_ava) > 0) {
        foreach ($not_ava as $item) {
            dbquery("UPDATE `$table` SET `status` = 6 WHERE `domain` = '$item';");
        }
    }
    if (count($ava) > 0) {
        foreach ($ava as $item) {
            dbquery("UPDATE `$table` SET `status` = 5 WHERE `domain` = '$item';");
        }
    }
}

function get_proxy()
{
    global $link, $login_data, $proxy_ip, $proxy_login;
    if (!$login_data) {
        $query = "SELECT DISTINCT `proxy` FROM `proxy` WHERE `proxy` != 0 and `used` != 4 ORDER BY `speed` DESC LIMIT 200";
        $login_data = dbquery($query, 1);
        if (count($login_data) == 0) {
            echo2("Нет больше не занятых проксей и аккаунтов! Проверить статусы!");
            exit();
        }
    } else {
        shuffle($login_data);
        $tmp = explode(":", $login_data[0]);
        $proxy_ip = $tmp[0] . ":" . $tmp[1];
        $proxy_login = $tmp[2] . ":" . $tmp[3];
    }
    return $login_data;
}

exit();

//Сливание базы с хоста с локальной
$db_insert = 'pin_houzz_dead_local';
$db_select = 'pin_houzz_dead';

$query = "SELECT `pin_id`,`domain`,`timestamp` FROM `$db_select` WHERE `status` = 0";
$insert = dbquery($query, 1);
foreach ($insert as $item) {
    $query = "INSERT INTO `$db_insert` SET `pin_id` = $item[0], `domain` = '$item[1]' , `timestamp` = '$item[2]'";
    if (dbquery($query, false, 1, false, 1) == 1) {
        $new++;
    }
}
echo2($new);
exit();
//$query = "SELECT * FROM `proxy` WHERE `iterations` > 1";
//$proxy = dbquery($query,1);
//$i = 0;
//foreach ($proxy as $item) {
//    $z = strtotime('17.03.2017 19:50') - strtotime($item[9]) ;
//    $proxy[$i][10] = $z;
//    $i++;
//}
//array_to_csv('proxy_speed.csv',$proxy,1);
//exit();
//$z = getmypid();
//basename($_SERVER['PHP_SELF']);
//$query = "SELECT `domain` FROM `pin_dead` WHERE `status` = 1";
//$res1 = dbquery($query,1);
//$query = "SELECT `domain` FROM `pin_top10`";
//$res2 = dbquery($query,1);
//    $res3 = array_diff($res1,$res2);
//foreach ($res3 as $item){
//    $query = "UPDATE `pin_dead` SET `status` = 0 WHERE `domain` = '$item'";
//    dbquery($query);
//}
//exit();

//$data = file("available.txt",FILE_IGNORE_NEW_LINES);
//foreach ($data as $item) {
//   $item = trim($item);
//   dbquery("UPDATE `pin_top10` SET `status` = 5 WHERE `domain` = '$item'");
//}
////exit();
////
//$data = file("not_available.txt",FILE_IGNORE_NEW_LINES);
//foreach ($data as $item) {
//   $item = trim($item);
//   dbquery("UPDATE `pin_top10` SET `status` = 6 WHERE `domain` = '$item'");
////    dbquery("UPDATE `pin_gold` SET `status` = 5 WHERE `domain` = '$item'");
//}
//exit();

//$query = "SELECT * FROM `pin_top10` WHERE `status` = 5 AND (`7_days_top10_pins_actions` > 100 OR `30_days_top10_pins_actions` > 200) ORDER BY `30_days_top10_pins_actions`  DESC;";
////$query = "SELECT * FROM `pin_gold` WHERE `status` = 4 ORDER BY `pins_total` DESC ";
//$result = dbquery($query);
//array_to_csv("top_domains.csv",$result,true);
//exit();
//$query = "SELECT * FROM `pin_check` WHERE `checked` = 0 LIMIT 1000";
//$pins_db = dbquery($query, 1, 1);
//foreach ($pins_db as $item) {
//    $md5 = md5($item[1]);
//    $query = "INSERT INTO `pin_check2` SET `pin` = '$item[1]' , `image_signature` = '$md5';";
//    dbquery($query);
//}
//exit();
//
//$pin_acc = 'inga.tarpavina.89@mail.ru';
//$pin_pwd = 'xmi0aJByoB';
//pinterest_local_login($pin_acc, $pin_pwd);
//
//$info = $bot->pins->info('168251736057693792');
//$domain = 'appthink.org';
//$pins = $bot->pins->fromSource($domain, 20)->toArray();

//foreach ($pins as $pin) {
//    $domain_pins['summary']['pins'] += 1;
//    $domain_pins['summary']['saves'] = $pin['aggregated_pin_data']['aggregated_stats']['saves'];
//    $domain_pins['summary']['done'] = $pin['aggregated_pin_data']['aggregated_stats']['done'];
//    $domain_pins['summary']['likes'] = $pin['aggregated_pin_data']['aggregated_stats']['likes'];
//    $domain_pins['summary']['repins'] = $pin['repin_count'];
//}
//$fp = fopen("com_whois_avail.txt", "a+");
//$domains = file('com_domains.txt', FILE_IGNORE_NEW_LINES);
//foreach ($domains as $sld) {
//
//    $domain = new Phois\Whois\Whois($sld);
//
//    $whois_answer = $domain->info();
//
////    echo $whois_answer;
//
//    if ($domain->isAvailable()) {
//        echo2("$sld");
//        fputs($fp, $sld . PHP_EOL);
//    } else {
////        echo "Domain is registered\n";
//    }
//}
//echo_time_wasted();
//exit();
//$str = '<item><title>LOVEVERAFTERBOOK.COM</title><link><![CDATA[https://auctions.godaddy.com/trpItemListing.aspx?miid=220201466&isc=rssTD01]]></link><description><![CDATA[Auction Type: BuyNow, Auction End Time: 03/09/2017 08:00 AM (PST), Price: $90, Number of Bids: 0, Domain Age: 5, Description: , Traffic: 4, Valuation: $0, IsAdult: false]]></description><guid><![CDATA[https://auctions.godaddy.com/trpItemListing.aspx?miid=220201466]]></guid></item>';
//preg_match('/Price: \$(\d+)/', $str, $matches2);
//preg_match('/\d{2}\/\d{2}\/\d{4} \d+:\d+ [AMPM]+/', $str, $matches3);
//$price = $matches2[1];
//$date = strtotime(substr($matches3[0], 0, -3));
//$time_to_moscow = 11*60*60; // PST - время которое выдает Godaddy (-8 Часов GMT), Москва +3 GMT.
//$moscow_end_date = $date + $time_to_moscow;
//$nice_end_date = date ('d/m H:i',$moscow_end_date);
//echo "penis";
//$query = "SELECT `id`,`domain` FROM `$pin_db` WHERE `7_days_top10_pins_actions` > 100 OR `30_days_top10_pins_actions` > 200 ORDER BY `30_days_top10_pins_actions`  DESC";
//$res = dbquery ($query,1);
//echo2 ("Выгрузили ".count($res)." доменов из базы $pin_db по запросу $query".PHP_EOL."Доступные домены: ");
//foreach ($res as $item ) {
//    if (checkdnsrr($item[1],'ns') == false) {
//        $query = "UPDATE `$pin_db` SET `domain_available` = 1 WHERE `id` = $item[0]";
//        echo2 ("$item[1]");
//        dbquery($query);
//        $z++;
//    } else {
//        $query = "UPDATE `$pin_db` SET `domain_available` = 0 WHERE `id` = $item[0]";
//        dbquery($query);
//        $i++;
//    }
//}
//echo_time_wasted(false,"Доступные для регистрации и нет домены $z / $i ");
//exit();


//$dirfiles = scandir("debug2");
//foreach ($dirfiles as $parsefname) {
//    if (strpos($parsefname, ".txt")) {
//        $txt = unserialize(file_get_contents("debug2/" . $parsefname));
//        $z = 0;
//    }
//}
//$z = file_get_contents('https://domainr.com/justeverydaycrap.com');

//$dirfiles = scandir("sources_expired");
//foreach ($dirfiles as $parsefname) {
//    if (strpos($parsefname, ".csv")) {
//        $csv_expireddomains_net = 'sources_expired/' . $parsefname;
//        $domains = csv_to_array2($csv_expireddomains_net, ';', 1, 1); // из файла с сайта https://member.expireddomains.net/domains/expiredcom201606/
//        $count += count ($domains);
//        foreach ($domains as $item) {
//            $query = "INSERT INTO `pinterest`.`domains` (`id`, `domain`, `status`) VALUES (NULL, '$item', '0')";
//            dbquery($query);
//        }
//    }
//}
$query = "SELECT * FROM `domains` WHERE `status` = 1 AND `domain` NOT LIKE '%hair%' AND `domain` NOT LIKE '%dress%' AND `domain` NOT LIKE '%fashion%' AND `domain` NOT LIKE '%style%' AND `domain` NOT LIKE '%tattoo%' ORDER BY `7_days_top10_pins_actions` DESC ";
//$csv = csv_to_array2("result/_big_hairs.csv", ';', null, 1);
//foreach ($csv as $item) {
//    $query = "INSERT INTO `pinterest`.`domains` (`id`, `domain`, `status`, `pins_total`, `boards_unique`, `pins_unique_url`, `saves`, `likes`, `repins`, `stolen_pins`, `stolen_saves`, `stolen_likes`, `stolen_repins`, `7_days_top10_pins_actions`, `30_days_top10_pins_actions`, `top10_pins_oldest_action`, `top1_pin_url`, `top1_pin_activity`)
//VALUES
//(NULL, '$item[0]', '1', $item[1], $item[2],$item[3],$item[4],$item[5],$item[6],$item[7],$item[8],$item[9],$item[10],$item[11],$item[12],$item[13],'$item[14]',$item[15]);";
//    dbquery($query);
//}
//SELECT * FROM `domains_feb` where `domain` REGEXP 'net$|com$|org$|info$|biz$|xyz$'
$dirfiles = scandir("sources_expired");
foreach ($dirfiles as $parsefname) {
    if (strpos($parsefname, ".txt")) {
        $txt = file("sources_expired/" . $parsefname, FILE_IGNORE_NEW_LINES);
        $count += count($txt);
        echo_time_wasted(null, "Начинаем обрабатывать файл $parsefname с " . count($txt) . " строками");
        foreach ($txt as $item) {
            $i++;
            if (preg_match('/^[0-9]+\./', $item) == false) {
                if (preg_match('/^[-a-z0-9]+\.biz|com|net|org|info|us|xyz|online|pro|tv|black|red$/', strtolower($item))) {
                    $count_valid++;
                    $query = "INSERT INTO `pinterest`.`$pin_db` (`id`, `domain`, `status`) VALUES (NULL, '$item', '0')";
                    if (dbquery($query, null, true, null, 'shutup') == 1) {
                        $count_uploaded++;
                    }
                }
            }
            if ($i % 10000 == 0) {
                echo2("    Идем по строке $i , загружено $count_uploaded");
            }
        }
        echo_time_wasted(null, "    Прошли по фильтру доменной зоны $count_valid , в базу догрузили $count_uploaded");
    }
}
$count_status = dbquery("SELECT COUNT(*) FROM `$pin_db` WHERE `status` = 0");
echo2("Загружено $count из файла доменов , из них прошли по фильтру доменной зоны $count_valid, всего в базе со статусом 0 $count_status , в базу догрузили $count_uploaded");
echo_time_wasted();
exit();

$date = strtotime('Tue, 21 Feb 2017 10:08:50 +0000');
echo date(DATE_RFC822, $date);
$secs = time() - $date;
$domains = csv_to_array2('sources/domains_06_2016.csv', ';', 1, 1);
echo "penis";
//Пример того что отдает активитес.
//$activities = $bot->pins->activity('378935756118457145', 5)->toArray();

function pinterest_local_login($pin_acc, $pin_pwd)
{
    global $bot;
    $bot = PinterestBot::create();
    $bot->auth->login($pin_acc, $pin_pwd);
    if ($bot->auth->isLoggedIn()) {
        echo2("login success! Local IP and $pin_acc:$pin_pwd");
    } else {
        echo2("login failed!");
        exit();
    }
}