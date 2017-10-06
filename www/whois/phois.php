<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 01.10.2017
 * Time: 2:22
 */
require '../../vendor/autoload.php';
$sld = 'korkinadrodze.info';

$domain = new Phois\Whois\Whois($sld);

$whois_answer = $domain->info();

echo $whois_answer;

if ($domain->isAvailable()) {
    echo "Domain is available\n";
} else {
    echo "Domain is registered\n";
}