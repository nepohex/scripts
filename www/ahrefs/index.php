<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 019.19.08.2018
 * Time: 10:27
 * Ahrefs Batch Domain Analytics
 */
$time = microtime(true);
$debug_mode = TRUE;

$url_list = file("./debug/input.txt", FILE_IGNORE_NEW_LINES);
$email = 'makar.bolinok.1977@mail.ru';
$pwd = 'jpG#iSKF9QHdzG';
$useragent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:61.0) Gecko/20100101 Firefox/61.0';
//SOCKS4 only! (fix in code if another!)
$proxy_ip = '92.63.195.131';
$proxy_port = '5055';
//$proxy_log_pwd = 'usr8100691:59f09b5b2c05e'; //Если нет логин-пароля, закомментировать строку

$urls_chunk = array_chunk($url_list, 200);

//Трижды пытаемся залогиниться
for ($i = 0; $i < 3; $i++) {
    $data = ahrefs_get_token();
    if ($data !== FALSE) {
        if (ahrefs_isAuth($data)) {
            break;
        } else {
            preg_match('/<input type="hidden" value="(.*)?" name="_token"/i', $data, $matches);
            //Если токен нашли, логинимся
            if (@count($matches) > 0) {
                $token = $matches[1];
                $data = ahrefs_login($proxy_ip, $proxy_port, $proxy_log_pwd, $useragent, $token, $email, $pwd);
                sleep(5);
            }
        }
    } else {
        echo2("No data from simple request - check proxy!");
    }
}

if (ahrefs_isAuth($data)) {
    echo2("Login Success!");
    $i = 0;
    $res = array(); //Результат
    foreach ($urls_chunk as $items) {
        while (@!stripos($header, 'attachment')) {
            $data = ahrefs_get_batch($items);

            $header = $data['header'];
            $body = $data['body'];

            if (stripos($header, 'attachment')) {
                echo2("Got Batch CSV!");

                $body = str_replace(',', "\t", $body); //CSV delimiter replace
                file_put_contents('./debug/result.csv', $body);

                $arr = preg_split('[\n]', $body);
                $res = array_merge($res, $arr);
                $res = array_unique($res);
                $string = implode("\n", $res);
                file_put_contents('./debug/result_full.csv', $string);
            } else if (stripos($body, 'please wait') !== FALSE) {
                $sleep_time = filter_var($body, FILTER_SANITIZE_NUMBER_INT);
                echo2("Making sleep. Got message from service - " . $body);
                sleep($sleep_time);
            } else {
                echo2("No result, no sleep time detected! Something strange!");
            }
        }
        unset ($header);
    }
} else {
    echo2("Login Failed!");
}
echo("to!");

function ahrefs_get_token()
{
    global $useragent, $proxy_ip, $proxy_port, $proxy_log_pwd;
    //Открываем страницу для получения логин-токена
    $ch = curl_init();
    $url = 'https://ahrefs.com/';
    curl_setopt($ch, CURLOPT_URL, $url); // отправляем на
    curl_setopt($ch, CURLOPT_HEADER, 0); // пустые заголовки
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);// таймаут4
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_COOKIEJAR, './debug/cookie.txt'); // сохранять куки в файл
    curl_setopt($ch, CURLOPT_COOKIEFILE, './debug/cookie.txt');

    curl_setopt($ch, CURLOPT_PROXY, $proxy_ip . ':' . $proxy_port);
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
    if ($proxy_log_pwd) {
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_log_pwd);
    }

    $data = curl_exec($ch);
    if ($data == FALSE) {
        echo2(curl_error($ch));
    }
    return $data;
}

function ahrefs_login($proxy_ip, $proxy_port, $proxy_log_pwd, $useragent, $token, $email, $pwd)
{
    $ch = curl_init();
    $url = 'https://ahrefs.com/user/login';
    curl_setopt($ch, CURLOPT_URL, $url); // отправляем на
    curl_setopt($ch, CURLOPT_HEADER, 0); // пустые заголовки
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);// таймаут4
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_COOKIEJAR, './debug/cookie.txt'); // сохранять куки в файл
    curl_setopt($ch, CURLOPT_COOKIEFILE, './debug/cookie.txt');
    curl_setopt($ch, CURLOPT_POST, 1); // использовать данные в post

    curl_setopt($ch, CURLOPT_PROXY, $proxy_ip . ':' . $proxy_port);
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
    if ($proxy_log_pwd) {
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_log_pwd);
    }

    curl_setopt($ch, CURLOPT_POSTFIELDS, array(
        '_token' => $token,
        'email' => $email,
        'password' => $pwd,
        'remember_me' => 1
    ));
    $data = curl_exec($ch);
    return $data;
}

//О том, что мы авторизовались будем судить по наличию формы logout
function ahrefs_isAuth($data)
{
    //<div class="dropdown-item">
//    <a class="dropdown-item--title block full signout" href="/user/logout" title="Sign out" data-dropdown-button-id="userAccountOptions">Sign out</a>
//                        </div>
    preg_match('/href="\/user\/logout" title="Sign out"/i', $data, $matches);
    if (@count($matches) > 0) {
        return TRUE;
    } else {
        return FALSE;
    }
}

function ahrefs_get_batch(array $url_list)
{
    global $useragent, $proxy_ip, $proxy_port, $proxy_log_pwd;
    $url_list = implode(PHP_EOL, $url_list);
    $ch = curl_init();
    $url = 'https://ahrefs.com/batch-analysis?export=1';
    curl_setopt($ch, CURLOPT_URL, $url); // отправляем на
    curl_setopt($ch, CURLOPT_HEADER, 1); // включить заголовки в ответ
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвратить то что вернул сервер
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // следовать за редиректами
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);// таймаут4
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// просто отключаем проверку сертификата
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_COOKIEJAR, './debug/cookie.txt'); // сохранять куки в файл
    curl_setopt($ch, CURLOPT_COOKIEFILE, './debug/cookie.txt');
    curl_setopt($ch, CURLOPT_POST, 1); // использовать данные в post

    curl_setopt($ch, CURLOPT_PROXY, $proxy_ip . ':' . $proxy_port);
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
    if ($proxy_log_pwd) {
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_log_pwd);
    }

    curl_setopt($ch, CURLOPT_POSTFIELDS, array(
        'batch_requests' => $url_list,
        'history_mode' => 'live',
        'mode' => 'auto',
        'need_submit_and_export' => '',
        'protocol' => 'http+++https',
        'sort_by' => ''
    ));
    $data = curl_exec($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($data, 0, $header_size);
    $body = substr($data, $header_size);

    $res['header'] = $header;
    $res['body'] = $body;

    return $res;
}

/** $fp_log задать как file handle (fopen) или название файла куда писать, файл будет создан по пути который указан в переменной.
 * @param $str Строка которую вывести
 * @param bool $double_log Метод логирования
 */
function echo2($str, $double_log = false)
{
    global $fp_log, $debug_mode, $double_log, $console_mode;
    if ($console_mode == false) {
        if ($double_log && $fp_log) {
            echo date("d-m-Y H:i:s") . " - " . $str . PHP_EOL;
            flush();
            if (is_resource($fp_log)) {
                fwrite($fp_log, date("d-m-Y H:i:s") . " - " . $str . PHP_EOL);
                return;
            } else {
                $fp = fopen($fp_log, 'a+');
                fwrite($fp, date("d-m-Y H:i:s") . " - " . $str . PHP_EOL);
                return;
            }
        }
        if ($debug_mode == true) {
            echo date("d-m-Y H:i:s") . " - " . $str . PHP_EOL;
            flush();
            return;
        }
        if ($fp_log) {
            if (is_resource($fp_log)) {
                fwrite($fp_log, date("d-m-Y H:i:s") . " - " . $str . PHP_EOL);
            } else {
                $fp = fopen($fp_log, 'a+');
                fwrite($fp, date("d-m-Y H:i:s") . " - " . $str . PHP_EOL);
            }
        }
    }
}