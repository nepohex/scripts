<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 22.02.2017
 * Time: 21:41
 */
include('../new/includes/functions.php');
require('../../vendor/autoload.php');
include('C:\OpenServer\domains\scripts.loc\www\php-whois\src\Phois\Whois\Whois.php');
use seregazhuk\PinterestBot\Factories\PinterestBot;
header('Content-Type: text/html; charset=utf-8');
$debug_mode = 1;
$db_pwd = '';
$db_usr = 'root';
//mysqli_connect2("pinterest");
$pin_db = 'domains_jan2';

$pin_acc = 'inga.tarpavina.89@mail.ru';
$pin_pwd = 'xmi0aJByoB';
pinterest_local_login($pin_acc, $pin_pwd);

$domain = 'appthink.org';
$pins = $bot->pins->fromSource($domain,20)->toArray();

foreach ($pins as $pin) {
    $domain_pins['summary']['pins'] += 1;
    $domain_pins['summary']['saves'] = $pin['aggregated_pin_data']['aggregated_stats']['saves'];
    $domain_pins['summary']['done'] = $pin['aggregated_pin_data']['aggregated_stats']['done'];
    $domain_pins['summary']['likes'] = $pin['aggregated_pin_data']['aggregated_stats']['likes'];
    $domain_pins['summary']['repins'] = $pin['repin_count'];
}
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
$activities = array(
    0 =>
        array(
            'timestamp' => 'Tue, 21 Feb 2017 10:08:50 +0000',
            'type' => 'repinactivity',
            'pin' =>
                array(
                    'edited_fields' =>
                        array(),
                    'pinner' =>
                        array(
                            'username' => 'MSdotP',
                            'id' => '456552618383173958',
                            'full_name' => 'Michael P',
                            'image_medium_url' => 'https://s-media-cache-ak0.pinimg.com/avatars/MSPaulovich_1438470247_75.jpg',
                        ),
                    'description_html' => 'I&#39;m personally not a face tattoo girl but still... this is a beautiful tattooed girl ♥',
                    'aggregated_pin_data' =>
                        array(
                            'type' => 'aggregatedpindata',
                            'image_signature' => 'b8547d9cd5cee8d7bb5acdffd7551acc',
                            'link' => 'http://myowntattoos.com/hot-tattoos/',
                            'id' => '4793464003552617674',
                            'aggregated_stats' =>
                                array(
                                    'saves' => 5308,
                                    'done' => 0,
                                    'likes' => 1882,
                                ),
                        ),
                    'created_at' => 'Tue, 21 Feb 2017 10:08:50 +0000',
                    'board' =>
                        array(
                            'name' => 'Sex Appeal',
                            'privacy' => 'public',
                            'url' => '/MSdotP/sex-appeal/',
                            'access' =>
                                array(),
                            'images' =>
                                array(
                                    '170x' =>
                                        array(
                                            0 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/23/50/d2/2350d20874d0d39f9f5287fc33ccf713.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#DB9461',
                                                    'height' => 113,
                                                ),
                                            1 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/c6/ef/3e/c6ef3eeeea41c8473a87f1a5dfb54d57.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#BEADA1',
                                                    'height' => 296,
                                                ),
                                            2 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/d6/1b/97/d61b9762b9eb8b44afb92bae02a320ec.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#8B4424',
                                                    'height' => 170,
                                                ),
                                            3 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/e7/08/87/e7088716d2e886ae96536fb468c99e5f.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#BEBCAF',
                                                    'height' => 127,
                                                ),
                                            4 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/8f/53/b9/8f53b942d44627372c3ac3eeb282227f.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#948C8E',
                                                    'height' => 254,
                                                ),
                                            5 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/2d/50/ce/2d50cecfc1f94a13444459661fe9b43d.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#EACBBA',
                                                    'height' => 211,
                                                ),
                                            6 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/8d/41/6c/8d416c7ebe4df52537354339154904c0.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#7B615B',
                                                    'height' => 187,
                                                ),
                                            7 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/50/c2/f1/50c2f133329728626309ce4f10e0fc9e.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#FAFAFA',
                                                    'height' => 258,
                                                ),
                                            8 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/76/74/2d/76742d57e806cf654ad3c02dc38a3b47.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#FDFDFD',
                                                    'height' => 592,
                                                ),
                                            9 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/1b/cf/4b/1bcf4bd7387a5b1f380e0aeba707c4d7.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#211318',
                                                    'height' => 283,
                                                ),
                                        ),
                                    '236x' =>
                                        array(
                                            0 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/23/50/d2/2350d20874d0d39f9f5287fc33ccf713.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#DB9461',
                                                    'height' => 157,
                                                ),
                                            1 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/c6/ef/3e/c6ef3eeeea41c8473a87f1a5dfb54d57.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#BEADA1',
                                                    'height' => 411,
                                                ),
                                            2 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/d6/1b/97/d61b9762b9eb8b44afb92bae02a320ec.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#8B4424',
                                                    'height' => 236,
                                                ),
                                            3 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/e7/08/87/e7088716d2e886ae96536fb468c99e5f.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#BEBCAF',
                                                    'height' => 176,
                                                ),
                                            4 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/8f/53/b9/8f53b942d44627372c3ac3eeb282227f.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#948C8E',
                                                    'height' => 353,
                                                ),
                                            5 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/2d/50/ce/2d50cecfc1f94a13444459661fe9b43d.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#EACBBA',
                                                    'height' => 293,
                                                ),
                                            6 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/8d/41/6c/8d416c7ebe4df52537354339154904c0.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#7B615B',
                                                    'height' => 260,
                                                ),
                                            7 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/50/c2/f1/50c2f133329728626309ce4f10e0fc9e.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#FAFAFA',
                                                    'height' => 358,
                                                ),
                                            8 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/76/74/2d/76742d57e806cf654ad3c02dc38a3b47.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#FDFDFD',
                                                    'height' => 823,
                                                ),
                                            9 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/1b/cf/4b/1bcf4bd7387a5b1f380e0aeba707c4d7.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#211318',
                                                    'height' => 393,
                                                ),
                                        ),
                                ),
                            'followed_by_me' => false,
                            'pin_count' => 175,
                            'id' => '456552549663996013',
                        ),
                    'id' => '456552480960859469',
                ),
        ),
    1 =>
        array(
            'timestamp' => 'Fri, 17 Feb 2017 18:03:17 +0000',
            'type' => 'repinactivity',
            'pin' =>
                array(
                    'edited_fields' =>
                        array(),
                    'pinner' =>
                        array(
                            'username' => 'lesleymohamad',
                            'id' => '562739053347230143',
                            'full_name' => 'Lesley Mohamad',
                            'image_medium_url' => 'https://s-media-cache-ak0.pinimg.com/avatars/lesleymohamad_1390926065_75.jpg',
                        ),
                    'description_html' => 'I&#39;m personally not a face/neck tattoo girl but still... this is a beautiful tattooed girl ♥',
                    'aggregated_pin_data' =>
                        array(
                            'type' => 'aggregatedpindata',
                            'image_signature' => 'b8547d9cd5cee8d7bb5acdffd7551acc',
                            'link' => 'http://myowntattoos.com/hot-tattoos/',
                            'id' => '4793464003552617674',
                            'aggregated_stats' =>
                                array(
                                    'saves' => 5308,
                                    'done' => 0,
                                    'likes' => 1882,
                                ),
                        ),
                    'created_at' => 'Fri, 17 Feb 2017 18:03:17 +0000',
                    'board' =>
                        array(
                            'name' => 'Inked',
                            'privacy' => 'public',
                            'url' => '/lesleymohamad/inked/',
                            'access' =>
                                array(),
                            'images' =>
                                array(
                                    '170x' =>
                                        array(
                                            0 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/1d/8a/b2/1d8ab21a0e1a4c0b09ef7360106d98dc.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#9F6A59',
                                                    'height' => 255,
                                                ),
                                            1 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/2a/3f/35/2a3f35c8f86e1d74220e48a4206c54f6.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#CDBAA5',
                                                    'height' => 128,
                                                ),
                                            2 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/b8/54/7d/b8547d9cd5cee8d7bb5acdffd7551acc.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#626262',
                                                    'height' => 229,
                                                ),
                                            3 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/0f/c2/09/0fc20988dac69a13a58c5b3ce4016f0b.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#818181',
                                                    'height' => 255,
                                                ),
                                            4 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/9f/d7/44/9fd744e598325dbb469724e5a8929959.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#525252',
                                                    'height' => 256,
                                                ),
                                            5 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/b9/58/fb/b958fb10ebfd579147fb578aeae07fd7.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#292424',
                                                    'height' => 302,
                                                ),
                                            6 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/ef/b3/74/efb374d1cd13a1f0b6c3f884e26bb667.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#443730',
                                                    'height' => 247,
                                                ),
                                            7 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/0a/71/2a/0a712a25d9600b9c0b343996995f506e.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#95784A',
                                                    'height' => 222,
                                                ),
                                            8 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/05/80/6c/05806c44cf1d6e294ade030e111ca36a.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#987F78',
                                                    'height' => 199,
                                                ),
                                            9 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/3f/71/3d/3f713d9bbaf82ebb8b408fe89b527039.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#7F726D',
                                                    'height' => 170,
                                                ),
                                        ),
                                    '236x' =>
                                        array(
                                            0 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/1d/8a/b2/1d8ab21a0e1a4c0b09ef7360106d98dc.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#9F6A59',
                                                    'height' => 354,
                                                ),
                                            1 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/2a/3f/35/2a3f35c8f86e1d74220e48a4206c54f6.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#CDBAA5',
                                                    'height' => 178,
                                                ),
                                            2 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/b8/54/7d/b8547d9cd5cee8d7bb5acdffd7551acc.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#626262',
                                                    'height' => 318,
                                                ),
                                            3 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/0f/c2/09/0fc20988dac69a13a58c5b3ce4016f0b.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#818181',
                                                    'height' => 354,
                                                ),
                                            4 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/9f/d7/44/9fd744e598325dbb469724e5a8929959.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#525252',
                                                    'height' => 355,
                                                ),
                                            5 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/b9/58/fb/b958fb10ebfd579147fb578aeae07fd7.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#292424',
                                                    'height' => 419,
                                                ),
                                            6 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/ef/b3/74/efb374d1cd13a1f0b6c3f884e26bb667.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#443730',
                                                    'height' => 343,
                                                ),
                                            7 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/0a/71/2a/0a712a25d9600b9c0b343996995f506e.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#95784A',
                                                    'height' => 308,
                                                ),
                                            8 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/05/80/6c/05806c44cf1d6e294ade030e111ca36a.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#987F78',
                                                    'height' => 277,
                                                ),
                                            9 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/3f/71/3d/3f713d9bbaf82ebb8b408fe89b527039.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#7F726D',
                                                    'height' => 236,
                                                ),
                                        ),
                                ),
                            'followed_by_me' => false,
                            'pin_count' => 239,
                            'id' => '562738984628170009',
                        ),
                    'id' => '562738915925126782',
                ),
        ),
    2 =>
        array(
            'timestamp' => 'Thu, 16 Feb 2017 23:20:39 +0000',
            'type' => 'repinactivity',
            'pin' =>
                array(
                    'edited_fields' =>
                        array(),
                    'pinner' =>
                        array(
                            'username' => 'tool4tec',
                            'id' => '802414996016350686',
                            'full_name' => 'Booma',
                            'image_medium_url' => 'https://s-media-cache-ak0.pinimg.com/avatars/tool4tec_1485738699_75.jpg',
                        ),
                    'description_html' => 'Perfekt',
                    'aggregated_pin_data' =>
                        array(
                            'type' => 'aggregatedpindata',
                            'image_signature' => 'b8547d9cd5cee8d7bb5acdffd7551acc',
                            'link' => 'http://myowntattoos.com/hot-tattoos/',
                            'id' => '4793464003552617674',
                            'aggregated_stats' =>
                                array(
                                    'saves' => 5308,
                                    'done' => 0,
                                    'likes' => 1882,
                                ),
                        ),
                    'created_at' => 'Thu, 16 Feb 2017 23:20:39 +0000',
                    'board' =>
                        array(
                            'name' => 'Tattoos',
                            'privacy' => 'public',
                            'url' => '/tool4tec/tattoos/',
                            'access' =>
                                array(),
                            'images' =>
                                array(
                                    '170x' =>
                                        array(
                                            0 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/37/76/2f/37762fad2cc3d870cbc3b8582213d2a0.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#A49182',
                                                    'height' => 323,
                                                ),
                                            1 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/bf/eb/36/bfeb366b4b4493770e70952a36dc4a81.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#33390D',
                                                    'height' => 254,
                                                ),
                                            2 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/88/11/f7/8811f78e7deb090262cd1687d9726796.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#AE9A96',
                                                    'height' => 257,
                                                ),
                                            3 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/02/66/af/0266afc55910c054a83e59a33df42a27.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#E1E3E7',
                                                    'height' => 209,
                                                ),
                                            4 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/91/97/55/919755e129b435de4af6d4a5711c5250.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#BDBFC0',
                                                    'height' => 284,
                                                ),
                                            5 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/d3/2b/48/d32b481a8634e0d6045eb52b0379f7f6.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#728079',
                                                    'height' => 290,
                                                ),
                                            6 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/4d/ba/eb/4dbaeb18f1879d2b46edbf0818f22c37.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#9BB4A9',
                                                    'height' => 211,
                                                ),
                                            7 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/c7/3f/35/c73f35f1eac12a749f900f2c2b854965.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#866857',
                                                    'height' => 170,
                                                ),
                                            8 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/35/55/96/35559660b9ba8fb4693d55f0498af4d4.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#88766F',
                                                    'height' => 200,
                                                ),
                                            9 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/4f/5b/ff/4f5bff379d46827c73c6c95d6a9eb97b.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#2F2E29',
                                                    'height' => 254,
                                                ),
                                        ),
                                    '236x' =>
                                        array(
                                            0 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/37/76/2f/37762fad2cc3d870cbc3b8582213d2a0.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#A49182',
                                                    'height' => 449,
                                                ),
                                            1 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/bf/eb/36/bfeb366b4b4493770e70952a36dc4a81.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#33390D',
                                                    'height' => 352,
                                                ),
                                            2 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/88/11/f7/8811f78e7deb090262cd1687d9726796.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#AE9A96',
                                                    'height' => 356,
                                                ),
                                            3 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/02/66/af/0266afc55910c054a83e59a33df42a27.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#E1E3E7',
                                                    'height' => 291,
                                                ),
                                            4 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/91/97/55/919755e129b435de4af6d4a5711c5250.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#BDBFC0',
                                                    'height' => 394,
                                                ),
                                            5 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/d3/2b/48/d32b481a8634e0d6045eb52b0379f7f6.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#728079',
                                                    'height' => 403,
                                                ),
                                            6 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/4d/ba/eb/4dbaeb18f1879d2b46edbf0818f22c37.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#9BB4A9',
                                                    'height' => 293,
                                                ),
                                            7 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/c7/3f/35/c73f35f1eac12a749f900f2c2b854965.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#866857',
                                                    'height' => 236,
                                                ),
                                            8 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/35/55/96/35559660b9ba8fb4693d55f0498af4d4.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#88766F',
                                                    'height' => 277,
                                                ),
                                            9 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/4f/5b/ff/4f5bff379d46827c73c6c95d6a9eb97b.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#2F2E29',
                                                    'height' => 353,
                                                ),
                                        ),
                                ),
                            'followed_by_me' => false,
                            'pin_count' => 1458,
                            'id' => '802414927296879420',
                        ),
                    'id' => '802414858578033343',
                ),
        ),
    3 =>
        array(
            'timestamp' => 'Thu, 16 Feb 2017 08:58:11 +0000',
            'type' => 'repinactivity',
            'pin' =>
                array(
                    'edited_fields' =>
                        array(),
                    'pinner' =>
                        array(
                            'username' => 'tjohnston110664',
                            'id' => '648025971290550898',
                            'full_name' => 'Tyler Johnston',
                            'image_medium_url' => 'https://s-media-cache-ak0.pinimg.com/avatars/tjohnston110664_1486078718_75.jpg',
                        ),
                    'description_html' => '<a class="pintag searchlink" data-query="%23Bonita" data-type="hashtag" href="/search/?q=%23Bonita&rs=hashtag" rel="nofollow" title="#Bonita search Pinterest">#Bonita</a>',
                    'aggregated_pin_data' =>
                        array(
                            'type' => 'aggregatedpindata',
                            'image_signature' => 'b8547d9cd5cee8d7bb5acdffd7551acc',
                            'link' => 'http://myowntattoos.com/hot-tattoos/',
                            'id' => '4793464003552617674',
                            'aggregated_stats' =>
                                array(
                                    'saves' => 5308,
                                    'done' => 0,
                                    'likes' => 1882,
                                ),
                        ),
                    'created_at' => 'Thu, 16 Feb 2017 08:58:11 +0000',
                    'board' =>
                        array(
                            'name' => 'Tyler\'s',
                            'privacy' => 'public',
                            'url' => '/tjohnston110664/tylers/',
                            'access' =>
                                array(),
                            'images' =>
                                array(
                                    '170x' =>
                                        array(
                                            0 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/79/f5/8d/79f58df9e51d2ec4749da7d61f14bff4.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#8B8080',
                                                    'height' => 238,
                                                ),
                                            1 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/14/d5/f2/14d5f264e254a457779b00629db93eee.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#401C24',
                                                    'height' => 234,
                                                ),
                                            2 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/77/3e/9b/773e9b4e9bed3d7c94d675d4b48bd74b.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#83A274',
                                                    'height' => 234,
                                                ),
                                            3 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/bf/c7/f4/bfc7f4ada7e3f9898d9b4c000df21aa6.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#262626',
                                                    'height' => 253,
                                                ),
                                            4 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/71/2b/78/712b78e39af6119696f807e220907488.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#393939',
                                                    'height' => 230,
                                                ),
                                            5 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/a6/11/05/a61105e897ddeead312b5a57a4818948.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#959595',
                                                    'height' => 209,
                                                ),
                                            6 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/d0/99/8c/d0998c8ad84e2041c8187ab174023ada.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#80564D',
                                                    'height' => 226,
                                                ),
                                            7 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/bb/3e/b2/bb3eb2ae02b509a5825cea13caa55ad8.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#121014',
                                                    'height' => 255,
                                                ),
                                            8 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/4c/c9/95/4cc9953ef2cefc7bbee4fe3eea8f8e0a.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#340A07',
                                                    'height' => 235,
                                                ),
                                            9 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/170x/c1/77/d9/c177d992fcc86d21b7626b6745b01d99.jpg',
                                                    'width' => 170,
                                                    'dominant_color' => '#636363',
                                                    'height' => 95,
                                                ),
                                        ),
                                    '236x' =>
                                        array(
                                            0 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/79/f5/8d/79f58df9e51d2ec4749da7d61f14bff4.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#8B8080',
                                                    'height' => 331,
                                                ),
                                            1 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/14/d5/f2/14d5f264e254a457779b00629db93eee.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#401C24',
                                                    'height' => 325,
                                                ),
                                            2 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/77/3e/9b/773e9b4e9bed3d7c94d675d4b48bd74b.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#83A274',
                                                    'height' => 326,
                                                ),
                                            3 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/bf/c7/f4/bfc7f4ada7e3f9898d9b4c000df21aa6.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#262626',
                                                    'height' => 352,
                                                ),
                                            4 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/71/2b/78/712b78e39af6119696f807e220907488.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#393939',
                                                    'height' => 319,
                                                ),
                                            5 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/a6/11/05/a61105e897ddeead312b5a57a4818948.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#959595',
                                                    'height' => 290,
                                                ),
                                            6 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/d0/99/8c/d0998c8ad84e2041c8187ab174023ada.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#80564D',
                                                    'height' => 314,
                                                ),
                                            7 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/bb/3e/b2/bb3eb2ae02b509a5825cea13caa55ad8.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#121014',
                                                    'height' => 354,
                                                ),
                                            8 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/4c/c9/95/4cc9953ef2cefc7bbee4fe3eea8f8e0a.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#340A07',
                                                    'height' => 326,
                                                ),
                                            9 =>
                                                array(
                                                    'url' => 'https://s-media-cache-ak0.pinimg.com/236x/c1/77/d9/c177d992fcc86d21b7626b6745b01d99.jpg',
                                                    'width' => 236,
                                                    'dominant_color' => '#636363',
                                                    'height' => 132,
                                                ),
                                        ),
                                ),
                            'followed_by_me' => false,
                            'pin_count' => 749,
                            'id' => '648025902571076669',
                        ),
                    'id' => '648025833852330283',
                ),
        ),
    4 =>
        array(
            'timestamp' => 'Thu, 16 Feb 2017 00:21:49 +0000',
            'type' => 'likepinactivity',
            'user' =>
                array(
                    'username' => 'cheyennenagy77',
                    'id' => '637189184687198121',
                    'full_name' => 'Cheyenne Nagy',
                    'image_medium_url' => 'https://s.pinimg.com/images/user/default_75.png',
                ),
        ),
);