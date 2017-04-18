<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 14.04.2017
 * Time: 22:47
 */
use Chumper\Zipper\Zipper;
include('../new/includes/functions.php');
require('../../vendor/autoload.php');

$z = 'C:\OpenServer\domains\scripts.loc\www\pinterest\extractor\3ppp.info';
function getDirContents($dir, &$results = array()){
    $files = scandir($dir);

    foreach($files as $key => $value){
        $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
        if(!is_dir($path)) {
            $results[] = $path;
        } else if($value != "." && $value != "..") {
            getDirContents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
}

$files = getDirContents($z);
$zipper = new \Chumper\Zipper\Zipper;

foreach ($files as $file) {
    $file_short = str_replace($z.'\\','',$file);
    $zipper->zip('test.zip')->folder(dirname($file_short))->add($file);
}
$zipper->make('test.zip')->folder('test')->add('includes.php');
$zipper->zip('test.zip')->folder('test')->add('composer.json','test');

$zipper->remove('composer.lock');

$zipper->folder('mySuperPackage')->add(
    array(
        'vendor',
        'composer.json'
    )
);

$zipper->getFileContent('mySuperPackage/composer.json');

$zipper->make('test.zip')->extractTo('',array('mySuperPackage/composer.json'),Zipper::WHITELIST);

$zipper->close();