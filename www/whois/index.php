<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 01.10.2017
 * Time: 2:01
 * WHOIS парсит, а вот доступность домена - нет.
 * #todo DONE перед проверкой доменов на Expired чекать какие еще не зарегали через дату Expiry Date.
 * #todo DONE понять какие записи во WHOIS когда домен реально удаляется и не регается, чтобы по регулярке отлавливать что он доступен к регистрации.
 * #1 пример korkinadrodze.info - освободился более 100 дней назад, доступен
 * https://prnt.sc/grrbxm
 * Domain Status    Deleted And Available Again
 * http://whois.domaintools.com/korkinadrodze.info
 * Выдает этот скрипт:
 * NOT FOUND
 * >>> Last update of WHOIS database: 2017-10-01T00:39:16Z <<<
 *
 * Access to AFILIAS WHOIS information is provided to assist persons in determining the contents of a domain name registration record in the Afilias registry database. The data in this record is provided by Afilias Limited for informational purposes only, and Afilias does not guarantee its accuracy.  This service is intended only for query-based access. You agree that you will use this data only for lawful purposes and that, under no circumstances will you use this data to(a) allow, enable, or otherwise support the transmission by e-mail, telephone, or facsimile of mass unsolicited, commercial advertising or solicitations to entities other than the data recipient's own existing customers; or (b) enable high volume, automated, electronic processes that send queries or data to the systems of Registry Operator, a Registrar, or Afilias except as reasonably necessary to register domain names or modify existing registrations. All rights reserved. Afilias reserves the right to modify these terms at any time. By submitting this query, you agree to abide by this policy.
 *
 * #2 пример chinaie.info - на 29 сентября (1 день назад), доступен
 * https://prnt.sc/grrce4
 * Domain Status    On-hold (generic)
 * http://whois.domaintools.com/chinaie.info
 * Выдает этот скрипт:
 * NOT FOUND
 * >>> Last update of WHOIS database: 2017-10-01T00:42:09Z <<<
 *
 * Access to AFILIAS WHOIS information is provided to assist persons in determining the contents of a domain name registration record in the Afilias registry database. The data in this record is provided by Afilias Limited for informational purposes only, and Afilias does not guarantee its accuracy.  This service is intended only for query-based access. You agree that you will use this data only for lawful purposes and that, under no circumstances will you use this data to(a) allow, enable, or otherwise support the transmission by e-mail, telephone, or facsimile of mass unsolicited, commercial advertising or solicitations to entities other than the data recipient's own existing customers; or (b) enable high volume, automated, electronic processes that send queries or data to the systems of Registry Operator, a Registrar, or Afilias except as reasonably necessary to register domain names or modify existing registrations. All rights reserved. Afilias reserves the right to modify these terms at any time. By submitting this query, you agree to abide by this policy.
 * Примеры 1-2 дают одинаковый ответ по WHOIS , хотя в Whois разные статусы стоят. Скрипт phois тоже выдает такие же даннные.
 * #todo DONE Написать регулярку отлавливать даты Registry Expiry Date: 2018-01-19T23:59:59Z , записывать все в лог, поставить на крон на VPS
 * [0-9]{4}-[0-9]{2}-[0-9]{2}
 * [0-9]{2}:[0-9]{2}:[0-9]{2}
 * #todo PROXY - не получилось ни через CURL, ни через socket_connection
 * todo Добавить проверку на ASIA регистраторов чтобы их сразу отсекать, примеры доменов :
 * ASIA DOMAINS
 * timmargh.net
 * FortBowieVineyards.net
 * Fx0418.net
 * southcoastsports.net - now!
 * summersvillememorial.org - now!
 * ace2009.org - now!
 * santedev.org
 * freegeekmichiana.org
 * bamiyanlaser.org
 * php-resources.org
 * images-strasbourg.org
 * gpninc.org
 * shoeman.org
 * lvamohawkhudson.org
 * SEPHICE.NET
 */
include '../../vendor/autoload.php';
include '../new/includes/functions.php';
use Cocur\Domain\Connection\ConnectionFactory;
use Cocur\Domain\Data\DataLoader;
use Cocur\Domain\Whois\Client as WhoisClient;

$debug_mode = 1;
$double_log = 1;
$fp_log = 'log.txt';
$tld_json = __DIR__ . '/tld.json';
$list_fp = __DIR__ . '/list.txt';
$rescsv_fp = __DIR__ . "/result.csv";
$restxt_fp = fopen(__DIR__ . '/result.txt', "a+");

$domains = array();

//debug
//$domainName = 'chinaie.info';

//get_whois('com.whois-servers.net', $domainName, '46.8.228.139:3128:maxeremin53210:m0jgYeOm');
$domains_list = file($list_fp, FILE_IGNORE_NEW_LINES);
$domains_list = clean_domains($domains_list);

//Build header
if (!is_file($rescsv_fp)) {
    $fp = fopen($rescsv_fp, "a+");
    fputcsv($fp, array('Domain Name', 'STATUS', 'Creation Date', 'Creation Time', 'Expiry Date', 'Expiry Time', 'Checking date', 'Checking Time'), ";");
} else {
    $fp = fopen($rescsv_fp, "a+");
    $csv = csv_to_array2($rescsv_fp, ';', '', TRUE);
}
////debug
//$a = 'Domain Name: MURMURES.INFO
//Registry Domain ID: D503300000048152717-LRMS
//Registrar WHOIS Server:
//Registrar URL: http://www.burnsidedomains.com
//Updated Date: 2017-10-02T05:56:49Z
//Creation Date: 2017-10-01T10:11:54Z
//Registry Expiry Date: 2018-10-01T10:11:54Z
//Registrar Registration Expiration Date:
//Registrar: Burnsidedomains.com LLC
//Registrar IANA ID: 1168
//Registrar Abuse Contact Email:
//Registrar Abuse Contact Phone:
//Reseller:
//Domain Status: clientTransferProhibited https://icann.org/epp#clientTransferProhibited
//Domain Status: serverTransferProhibited https://icann.org/epp#serverTransferProhibited
//Domain Status: addPeriod https://icann.org/epp#addPeriod
//Registry Registrant ID: C209602494-LRMS
//Registrant Name: PERFECT PRIVACY, LLC
//Registrant Organization:
//Registrant Street: 12808 Gran Bay Parkway West
//Registrant City: Jacksonville
//Registrant State/Province: FL
//Registrant Postal Code: 32258
//Registrant Country: US
//Registrant Phone: +1.9027492701
//Registrant Phone Ext:
//Registrant Fax:
//Registrant Fax Ext:
//Registrant Email: 65cgio53lsi0qa97hsmhh0f6ue@domaindiscreet.com
//Registry Admin ID: C209602494-LRMS
//Admin Name: PERFECT PRIVACY, LLC
//Admin Organization:
//Admin Street: 12808 Gran Bay Parkway West
//Admin City: Jacksonville
//Admin State/Province: FL
//Admin Postal Code: 32258
//Admin Country: US
//Admin Phone: +1.9027492701
//Admin Phone Ext:
//Admin Fax:
//Admin Fax Ext:
//Admin Email: 65cgio53lsi0qa97hsmhh0f6ue@domaindiscreet.com
//Registry Tech ID: C209602494-LRMS
//Tech Name: PERFECT PRIVACY, LLC
//Tech Organization:
//Tech Street: 12808 Gran Bay Parkway West
//Tech City: Jacksonville
//Tech State/Province: FL
//Tech Postal Code: 32258
//Tech Country: US
//Tech Phone: +1.9027492701
//Tech Phone Ext:
//Tech Fax:
//Tech Fax Ext:
//Tech Email: 65cgio53lsi0qa97hsmhh0f6ue@domaindiscreet.com
//Registry Billing ID: C209602250-LRMS
//Billing Name: PERFECT PRIVACY, LLC
//Billing Organization:
//Billing Street: 12808 Gran Bay Parkway West
//Billing City: Jacksonville
//Billing State/Province: FL
//Billing Postal Code: 32258
//Billing Country: US
//Billing Phone: +1.9027492701
//Billing Phone Ext:
//Billing Fax:
//Billing Fax Ext:
//Billing Email: birg1sshpkjmoaphrjn2rujfa0@domaindiscreet.com
//Name Server: NS1.VOODOO.COM
//Name Server: NS2.VOODOO.COM
//DNSSEC: unsigned
//URL of the ICANN Whois Inaccuracy Complaint Form: https://www.icann.org/wicf/
//>>> Last update of WHOIS database: 2017-10-03T06:41:08Z <<<
//
//For more information on Whois status codes, please visit https://icann.org/epp
//
//Access to AFILIAS WHOIS information is provided to assist persons in determining the contents of a domain name registration record in the Afilias registry database. The data in this record is provided by Afilias Limited for informational purposes only, and Afilias does not guarantee its accuracy.  This service is intended only for query-based access. You agree that you will use this data only for lawful purposes and that, under no circumstances will you use this data to(a) allow, enable, or otherwise support the transmission by e-mail, telephone, or facsimile of mass unsolicited, commercial advertising or solicitations to entities other than the data recipient\'s own existing customers; or (b) enable high volume, automated, electronic processes that send queries or data to the systems of Registry Operator, a Registrar, or Afilias except as reasonably necessary to register domain names or modify existing registrations. All rights reserved. Afilias reserves the right to modify these terms at any time. By submitting this query, you agree to abide by this policy.
//';

//Счетчик сколько доменов изменили статус с прошлого запуска
$c_changed_statuses = 0;
//Счетчик сколько доменов уже перерегали
$c_renewed = 0;
//Сколько просто проверили и дали статус Checked
$c_checked = 0;

$factory = new ConnectionFactory();
$dataLoader = new DataLoader();
$data = $dataLoader->load($tld_json);
$client = new WhoisClient($factory, $data);

//Проверяем надо ли чекать домен исходя из предыдущего статуса записанного в CSV.
if ($csv) {
    foreach ($csv as $item) {
        $k = array_search($item[0], $domains_list);
        if ($k !== FALSE) {
            unset ($domains_list[$k]);
        }
    }
    $csv = array_merge($csv, $domains_list);
    foreach ($csv as $item) {
        if (is_array($item)) {
            if (check_needed($item[1])) {
                $domains[] = $item[0];
            }
        } else {
            if (check_needed($item)) {
                $domains[] = $item;
            }
        }
    }
    // Максимально угребищно! Надо делать кароче DB и чистить все это сраное гавно.
    if (count($domains) > 0) {
        foreach ($domains as $key => $domain) {
            foreach ($csv as $item) {
                if ($domain == $item[0]) {
                    if (in_array(array('RENEWED', 'NO DATA'), $item)) {
                        $to_clean[] = $domain;
                        break;
                    }
                }
            }
        }
        if (is_array($to_clean)) {
            $domains = array_diff($domains, $to_clean);
        }
    }
    $domains = array_unique($domains);
    if (count($domains) == 0) {
        echo2("No domains to check!");
        send_email("No domains to check!", "Gimme moar domains to check!");
        exit();
    }
} else {
    $domains = $domains_list;
}
unset ($csv, $domains_list);

foreach ($domains as $domainName) {
    sleep(2);
//Check WHOIS
    $a = $client->query($domainName);
    // debug
    //echo2("$a");
    $b = get_expiry_date($a);
    if ($b) {
        if (already_renewed($b[4], 30)) {
            echo2("Домен $domainName уже перерегистрировали!");
            $res_arr = array($domainName, 'RENEWED', $b[1], $b[2], $b[4], $b[5], date("Y-m-d"), date("H:i:s"));
            $pack_str = pack('A30A10A12A10A12A10A12A10', $res_arr[0], $res_arr[1], $res_arr[2], $res_arr[3], $res_arr[4], $res_arr[5], $res_arr[6], $res_arr[7]);
            fputcsv($fp, $res_arr, ";");
            // echo2($pack_str); //debug
            fwrite($restxt_fp, $pack_str . PHP_EOL);
            $email[$domainName][] = "RENEWED";
            $c_renewed++;
        } else {
            $res_arr = array($domainName, 'CHECKED', $b[1], $b[2], $b[4], $b[5], date("Y-m-d"), date("H:i:s"));
            $pack_str = pack('A30A10A12A10A12A10A12A10', $res_arr[0], $res_arr[1], $res_arr[2], $res_arr[3], $res_arr[4], $res_arr[5], $res_arr[6], $res_arr[7]);
            fputcsv($fp, $res_arr, ";");
            fwrite($restxt_fp, $pack_str . PHP_EOL);
            $c_checked++;
        }
    } else {
        $c_changed_statuses++;
        echo2("Домен $domainName не смогли получить из WHOIS даты Creation / Expiry регуляркой!");
        $res_arr = array($domainName, 'NO DATA', '-', '-', '-', '-', date("Y-m-d"), date("H:i:s"));
        $pack_str = pack('A30A10A12A10A12A10A12A10', $res_arr[0], $res_arr[1], $res_arr[2], $res_arr[3], $res_arr[4], $res_arr[5], $res_arr[6], $res_arr[7]);
        fputcsv($fp, $res_arr, ";");
        fwrite($restxt_fp, $pack_str . PHP_EOL);
        $email[$domainName][] = 'NO DATA';
    }
}
if ($c_changed_statuses > 0) {
    send_email("$c_changed_statuses domains Statuses", print_r($email, true));
}
if ($c_renewed == count($domains)) {
    send_email("$c_renewed - all domains in list already RENEWED!", print_r($email, true));
}
echo2("Проверили всего " . count($domains) . " доменов, из них $c_changed_statuses изменили статусы , $c_renewed уже обновлены. $c_checked доменов еще недоступны для регистрации.");
fclose($fp);
/* Каждый раз когда правим регулярку, особенно группы ( ) , нужно проверить после этого как обрабатываются и в каких индексах массива лежат данные
 *
 */
function get_expiry_date($string)
{
    preg_match('/Creation Date.*([0-9]{4}-[0-9]{2}-[0-9]{2}).*([0-9]{2}:[0-9]{2}:[0-9]{2})(\t|.|\n)*Registry Expiry Date.*([0-9]{4}-[0-9]{2}-[0-9]{2}).*([0-9]{2}:[0-9]{2}:[0-9]{2})/i', $string, $matches);
    if ($matches) {
        return $matches;
    } else {
        return false;
    }
}

/**
 * Функция проверяет был ли уже перереган домен, если у него указана Expiry date.
 * Проверяем дата Expiry наступит позже чем 30 дней или раньше.
 * @param $date
 * @param int $days
 * @return bool
 */
function already_renewed($date, $days = 30)
{
    $now = time();
    if ($exp_date = strtotime($date)) {
        if (($exp_date - $now) < ($days * 86400)) {
            return false;
        } else {
            return true;
        }
    } else {
        echo2("Полученное регуляркой значение не преобразуется в дату! Выходим!");
        exit ();
    }
}

function send_email($theme, $msg)
{
    $mail = mail("moscowbomj@gmail.com", $theme, $msg);
    if (!$mail) {
        $errorMessage = error_get_last()['message'];
    }
}


function check_needed($status, $states_no_check = array('RENEWED', 'NO DATA'))
{
    if (in_array($status, $states_no_check)) {
        return FALSE;
    } else {
        return TRUE;
    }
}

function clean_domains($domain_arr)
{
    $replace = array('www.', 'http://', '/');
    $domain_arr = str_replace($replace, '', $domain_arr);
    $domain_arr = array_map('trim', $domain_arr);
    return $domain_arr;
}

/** Без прокси работает, с прокси - нет
 * @param $server
 * @param $domain
 * @param bool $proxy
 * @return mixed
 */
function get_whois($server, $domain, $proxy = FALSE)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $server);
    curl_setopt($ch, CURLOPT_PORT, 43);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    if ($proxy) {
        $proxy = explode(":", $proxy);
        curl_setopt($ch, CURLOPT_PROXY, $proxy[0] . ":" . $proxy[1]);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy[2] . ":" . $proxy[3]);
    }
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $domain . "\r\n");
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
// из файла StreamConnection.php - моя попытка proxy, так и не получилось. Яндекс нормально получаем, а дальше не понятно.
//$auth = base64_encode('maxeremin53209:wy05t6Xw');
//$header = array("Proxy-Authorization: Basic $auth");
//$ctx = stream_context_create(array(
//        'http' => array(
//            'proxy' => 'tcp://46.8.228.138:3128',
//            'request_fulluri' => TRUE,
//            'header' => $header
//        ),
//        'tcp' => array(
//            'proxy' => 'tcp://46.8.228.138:3128',
//            'request_fulluri' => TRUE,
//            'header' => $header
//        )
//    )
//);
//$this->connection = file_get_contents("http://ya.ru", false, $ctx);
//$this->connection = @stream_socket_client(sprintf('tcp://%s:%d', $hostname, $port), $errno, $errstr, null, null, $ctx);