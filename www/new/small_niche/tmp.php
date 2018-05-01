<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 26.04.2018
 * Time: 23:09
 */
$tmp = file('f:\Dumps\mfa_humanbody.com\texts.txt', FILE_IGNORE_NEW_LINES);
echo (filesize('f:\Dumps\mfa_humanbody.com\texts.txt')) . PHP_EOL;
foreach ($tmp as $k => $row) {
    $tmp[$k] .= PHP_EOL;
    if (mb_strlen($row) < 150) {
        $i++;
        unset ($tmp[$k]);
    }
}
file_put_contents('f:\Dumps\mfa_humanbody.com\texts_no_short.txt', $tmp);
echo(filesize('f:\Dumps\mfa_humanbody.com\texts_no_short.txt'));
