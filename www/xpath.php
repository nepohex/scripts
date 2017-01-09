<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 05.01.2017
 * Time: 23:24
 */

$html = file_get_contents("theright.txt");

$tidy = new tidy;
$tidy->parseString($html);
$tidy->cleanRepair();
$clean_html = $tidy->value;

$dom = new DomDocument();
$dom->loadHTML( $clean_html );
$xpath = new DomXPath( $dom );

$tag1 = $xpath->getElementsByTagName("h3")->item(0);
$_res = $xpath->query("//h3");

print_r($_res);
