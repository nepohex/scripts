<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 28.02.2018
 * Time: 14:33
 */
require '../../vendor/autoload.php';
require_once '../new/includes/functions.php';
require 'C:\OpenServer\domains\scripts.loc\www\socks_proxy\socks_whois_function.php';
ini_set("ERROR_REPORTING", E_ALL);
$debug_mode = 1;
$double_log = 1;
$fp_log = './debug/log.txt';
mysqli_connect2('dev_rudomains');

$tld_json = json_decode(file_get_contents('C:\OpenServer\domains\scripts.loc\www\socks_proxy\tld.json'));
$proxy_list = file('f:\tmp\socks_proxy.txt', FILE_IGNORE_NEW_LINES);

//$tmp = preg_match_all('/Creation Date.*([0-9]{4}-[0-9]{2}-[0-9]{2})/i', $tmp, $tmp2);

$query1 = "SELECT * FROM `webomer` WHERE `whois_checked` = 0 AND `place` < 300000;";
$res = mysqli_query($link, $query1);
$i = 1;
while ($row = mysqli_fetch_assoc($res)) {
    $i++;

    //Если домен длиннее 40 символов (в базе максимум 50 символов хранится)
    if (strlen($row['site_url']) > 40) {
        dbquery("UPDATE `dev_rudomains`.`webomer` SET `whois_checked` = 3 WHERE `id` = $row[id];");
        continue;
    }

    $proxy = tmp_get_rand_prox($proxy_list);
    $whois_response = @whois_socks_proxy($SocksClient, $proxy, $tld_json, $row['site_url']);

    //Если нет TLD сервера под домен (или вообще неверно задана зона), ставим ошибку 4.
    if ($whois_response == 'NO TLD SERVER') {
        dbquery("UPDATE `dev_rudomains`.`webomer` SET `whois_checked` = 4 WHERE `id` = $row[id];");
        continue;
    } else if ($whois_response == FALSE) {
        //Это если ошибка прокси или уже сервера Whois
        dbquery("UPDATE `dev_rudomains`.`webomer` SET `whois_checked` = 5 WHERE `id` = $row[id];");
        $error += 1;
        if ($error >= 10) {
            echo2("10 Ошибки от Whois подряд!");
            exit();
        }
        continue;
    }

    $d_data = tmp_parse_whois_response($whois_response);
    if ($d_data['Creation Date']) {
        //Get right date
        $db_format_date = date("d.m.Y", strtotime($d_data['Creation Date']));
        $d_year = end(explode(".", $db_format_date));
        $tld_id = $row['tld_id'];

        if ($d_data['Registrar']) {
            $tmp = strtolower($d_data['Registrar']);
            $registrant_id = regru_get_registrant($tmp);
        } else {
            $registrant_id = 69; //id for unknown registrant
        }
        //Insert data
        $query[] = "INSERT INTO `dev_rudomains`.`domains` VALUES ('','$row[site_url]',$registrant_id,'$db_format_date','','',1,$d_year,$row[id],1,$tld_id);";
        $query[] = "UPDATE `dev_rudomains`.`webomer` SET `whois_checked` = 1 WHERE `id` = $row[id];";
    } else {
        //Если не смогли распарсить дату создания, но получили ответ от Whois сервера - ставим ошибку 2
        $query[] = "UPDATE `dev_rudomains`.`webomer` SET `whois_checked` = 2 WHERE `id` = $row[id];";
    }
    //debug
//    $tmp = pack('A30A20A20A20', $row['site_url'], $proxy[0], $d_data['Creation Date'], $db_format_date);
//    echo2($tmp);
    //

    dbquery($query);
    unset ($query, $error);

    if ($i % 1000 == 0) {
        echo_time_wasted($i);
    }
}

function regru_get_registrant($regname)
{
    if ($res = dbquery("SELECT `id` FROM `dev_rudomains`.`registrant` WHERE `registrant` = '$regname';")) {
        return $res;
    } else {
        dbquery("INSERT INTO `dev_rudomains`.`registrant` VALUES ('','$regname');");
        return dbquery("SELECT `id` FROM `dev_rudomains`.`registrant` WHERE `registrant` = '$regname';");
    }
}

function tmp_get_rand_prox(array $tmp)
{
    shuffle($tmp);
    $tmp2 = last($tmp);
    $tmp2 = explode(":", $tmp2);
    return $tmp2;
}

function tmp_parse_whois_response($response)
{
    preg_match('/Creation Date.*([0-9]{4}-[0-9]{2}-[0-9]{2})/i', $response, $tmp2);
    $tmp['Creation Date'] = @trim($tmp2[1]);
    preg_match('/registrar:(.*)/i', $response, $tmp2);
    $tmp['Registrar'] = @trim($tmp2[1]);
    return $tmp;
}
//SIMILARWEB FRAGMENT
//$options = array(
//    'http'=>array(
//        'method'=>"GET",
//        'header'=>"Accept-language: en\r\n" .
//            "Cookie: foo=bar\r\n" .  // check function.stream-context-create on php.net
//            "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" // i.e. An iPad
//    )
//);
//
//$context = stream_context_create($options);
//$file = file_get_contents('https://www.similarweb.com/website/ladykiss.ru', false, $context);
////SIMILARWEB JSON Example from text
//$json = '[{"overview":{"Date":"2018-01-01T00:00:00","Country":643,"AdNetworks":{"Count":2,"Data":[["Yandex Direct","",1.0,0],["Other","",0.0,0]]},"TrafficSources":{"Direct":0.12833584166646606,"Referrals":0.026762434356231733,"Search":0.84179493588639254,"Social":0.0026483349265867466,"Mail":0.00014965152922915737,"Paid Referrals":0.00030880163509396585},"IsVerifiedData":false,"Icon":"https://site-images.similarcdn.com/image?url=ladykiss.ru&t=2&s=1&h=9600866000721581237","TopCountryShares":[[643.0,0.702291355539096,-0.20685092793121204],[804.0,0.11541691936868721,-0.21140448082594562],[112.0,0.032967159834138914,-0.31753454772904566],[398.0,0.023681601272800147,-0.3172660887962574],[428.0,0.017420064251515103,1.8356964059624905]],"RedirectUrl":"ladykiss.ru","Category":"Home_and_Garden/Nursery_and_Playroom","GlobalRank":[456994,0,62160,0],"CategoryRank":[441,0,62,0],"IsSiteBigAndGaDiscrepancyHigh":false,"EngagementsGA":null,"EngagementsSimilarweb":{"BounceRate":"82.34%","PageViews":1.2241097466761395,"TimeOnSite":"00:00:40","TotalLastMonthVisits":187168,"TotalRelativeChange":0.20783939184698086,"LastEngagementYear":2018,"LastEngagementMonth":1,"WeeklyTrafficNumbers":{"2017-08-01":236840,"2017-09-01":211797,"2017-10-01":218538,"2017-11-01":155513,"2017-12-01":154961,"2018-01-01":187168}},"CountryRanks":{"643":[37851,0,2128,0]},"Referrals":{"destination":[{"Site":"samplesociety.ru","Value":0.21690620418925363,"Change":0.61617887864431875},{"Site":"elenakrygina.com","Value":0.21568405781166236,"Change":0.79135650296402493},{"Site":"do-rod.com","Value":0.084209644449773,"Change":0.0},{"Site":"eur.platok-moda.ru","Value":0.052314739570231368,"Change":0.0},{"Site":"yandex.ru","Value":0.037477386516575291,"Change":0.0}],"referrals":[{"Site":"go.mail.ru","Value":0.78712003804658492,"Change":0.94738951328522958},{"Site":"yandex.ru","Value":0.11297683757601187,"Change":2.7147496892772631},{"Site":"2nancy.ru","Value":0.026071577902156584,"Change":0.0},{"Site":"mypensiya.mirtesen.ru","Value":0.026071577902156584,"Change":0.0},{"Site":"poshukach.com","Value":0.012997864703547435,"Change":-0.62040043975325621}]},"IsSmallSite":false,"SimilarSites":[{"Site":"hair-ok.ru","AltImage":"hair-ok.ru","ImageUrl":"https://site-images.similarcdn.com/image?url=hair-ok.ru&t=0&s=1&h=2923079798005815354","IconUrl":"https://site-images.similarcdn.com/image?url=hair-ok.ru&t=2&s=1&h=2923079798005815354","EllipsiseName":"hair-ok.ru","CapitalizedSite":null,"Rank":1732293,"IsBlackListed":false},{"Site":"bianca-lux.ru","AltImage":"bianca-lux.ru","ImageUrl":"https://site-images.similarcdn.com/image?url=bianca-lux.ru&t=0&s=1&h=2854589011728757912","IconUrl":"https://site-images.similarcdn.com/image?url=bianca-lux.ru&t=2&s=1&h=2854589011728757912","EllipsiseName":"bianca-lux.ru","CapitalizedSite":null,"Rank":510598,"IsBlackListed":false},{"Site":"nasmachne.com","AltImage":"Nasmachne","ImageUrl":"https://site-images.similarcdn.com/image?url=nasmachne.com&t=0&s=1&h=14630348583865050700","IconUrl":"https://site-images.similarcdn.com/image?url=nasmachne.com&t=2&s=1&h=14630348583865050700","EllipsiseName":"nasmachne.com","CapitalizedSite":null,"Rank":0,"IsBlackListed":false},{"Site":"y-jenchina.ru","AltImage":"y-jenchina.ru","ImageUrl":"https://site-images.similarcdn.com/image?url=y-jenchina.ru&t=0&s=1&h=12847660745671006936","IconUrl":"https://site-images.similarcdn.com/image?url=y-jenchina.ru&t=2&s=1&h=12847660745671006936","EllipsiseName":"y-jenchina.ru","CapitalizedSite":null,"Rank":289336,"IsBlackListed":false},{"Site":"womenspeaks.ru","AltImage":"womenspeaks.ru","ImageUrl":"https://site-images.similarcdn.com/image?url=womenspeaks.ru&t=0&s=1&h=7752288847577337479","IconUrl":"https://site-images.similarcdn.com/image?url=womenspeaks.ru&t=2&s=1&h=7752288847577337479","EllipsiseName":"womenspeaks.ru","CapitalizedSite":null,"Rank":3019913,"IsBlackListed":false},{"Site":"viva-woman.ru","AltImage":"viva-woman.ru","ImageUrl":"https://site-images.similarcdn.com/image?url=viva-woman.ru&t=0&s=1&h=13998647752256983057","IconUrl":"https://site-images.similarcdn.com/image?url=viva-woman.ru&t=2&s=1&h=13998647752256983057","EllipsiseName":"viva-woman.ru","CapitalizedSite":null,"Rank":1201550,"IsBlackListed":false},{"Site":"volosomagia.ru","AltImage":"volosomagia.ru","ImageUrl":"https://site-images.similarcdn.com/image?url=volosomagia.ru&t=0&s=1&h=4064915294536093342","IconUrl":"https://site-images.similarcdn.com/image?url=volosomagia.ru&t=2&s=1&h=4064915294536093342","EllipsiseName":"volosomagia.ru","CapitalizedSite":null,"Rank":460134,"IsBlackListed":false},{"Site":"buro3.megaplan.ru","AltImage":"buro3.megaplan.ru","ImageUrl":"/images/unknown-medium.png","IconUrl":"https://site-images.similarcdn.com/image?url=buro3.megaplan.ru&t=2&s=1&h=9482706438931414241","EllipsiseName":"buro3.megaplan.ru","CapitalizedSite":null,"Rank":0,"IsBlackListed":false},{"Site":"prichesok.net","AltImage":"prichesok.net","ImageUrl":"https://site-images.similarcdn.com/image?url=prichesok.net&t=0&s=1&h=6883610524888126199","IconUrl":"https://site-images.similarcdn.com/image?url=prichesok.net&t=2&s=1&h=6883610524888126199","EllipsiseName":"prichesok.net","CapitalizedSite":null,"Rank":1638114,"IsBlackListed":false},{"Site":"modagid.ru","AltImage":"modagid.ru","ImageUrl":"https://site-images.similarcdn.com/image?url=modagid.ru&t=0&s=1&h=5885046686837108993","IconUrl":"https://site-images.similarcdn.com/image?url=modagid.ru&t=2&s=1&h=5885046686837108993","EllipsiseName":"modagid.ru","CapitalizedSite":null,"Rank":167711,"IsBlackListed":false}]}}]';
//$json = json_decode($json);

