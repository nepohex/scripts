<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 28.02.2018
 * Time: 1:26
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

$domainName = 'pravo.guru';

$factory = new ConnectionFactory();
$dataLoader = new DataLoader();
$data = $dataLoader->load($tld_json);
$client = new WhoisClient($factory, $data);

$a = $client->query($domainName);

$tmp = preg_match_all('/Creation Date.*([0-9]{4}-[0-9]{2}-[0-9]{2})/i', $a, $tmp2);
//$tmp = get_expiry_date($a);
//
//function get_expiry_date($string)
//{
//    preg_match('/Creation Date.*([0-9]{4}-[0-9]{2}-[0-9]{2}).*([0-9]{2}:[0-9]{2}:[0-9]{2})(\t|.|\n)*Registry Expiry Date.*([0-9]{4}-[0-9]{2}-[0-9]{2}).*([0-9]{2}:[0-9]{2}:[0-9]{2})/i', $string, $matches);
//    if ($matches) {
//        return $matches;
//    } else {
//        return false;
//    }
//}

print_r($tmp2);
echo2("yo");