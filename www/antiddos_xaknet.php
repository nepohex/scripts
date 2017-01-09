<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 11.12.2016
 * Time: 2:14
 */
class antiDdos
{
    // дебаг
    public $debug = false;
    // директория для хранения файлов индефикации запросов
    public $dir = '_bots/';
    // номер icq администратора
    public $icq = '123456';
    // сообщение при выключенном сайте
    public $off_message = 'Временные неполадки, пожалуйста, подождите.';
    // индивидуальный индефикатор
    private $indeficator = null;
    // сообщение при бане, работают шаблоны, можно использовать - {ICQ}, {IP}, {UA}, {DATE}
    public $ban_message = 'Вы были заблокированы antiddos системой.
                          Если это ошибка обратитесь к администратору, icq of admin: {ICQ}
                          <hr>(c)XakNet antiddos module, ваш IP - {IP}(<i>{UA}</i>), date - {DATE}';
    // команда выполнения бана в файрволле
    public $exec_ban = 'iptables -A INPUT -s {IP} -j DROP';
    // тип защиты от ддоса:
    /* Возможные значения $ddos 1-5:
    | 1. Простая проверка по кукам, по умолчанию(рекомендую)
    | 2. Двойная проверка через $_GET antiddos и meta refresh
    | 3. Запрос на авторизацию WWW-Authenticate
    | 4. полное отключение сайта, боты не блокируются!!!
    | 5. выключать сайт если нагрузка слишком большая на сервере, боты не блокируются!!!
    */
    var $ddos = 1;
    // часть домена поисковых ботов, см strpos()
    private $searchbots = array('googlebot.com', 'yandex.ru', 'ramtel.ru', 'rambler.ru', 'aport.ru', 'sape.ru', 'msn.com', 'yahoo.net');
    // временная переменные нужные для работы скрипта
    private $attack = false;
    private $is_bot = false;
    private $ddosuser;
    private $ddospass;
    private $load;
    public $maxload = 80;

    function __construct($debug)
    {
        @session_start() or die('session_start() filed!');
        $this->indeficator = md5(sha1('botik' . strrev(getenv('HTTP_USER_AGENT'))));
        $this->ban_message = str_replace(array('{ICQ}', '{IP}', '{UA}', '{DATE}'),
            array($this->icq, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], date('d.m.y H:i')),
            $this->ban_message
        );
        if (eregi(ip2long($_SERVER['REMOTE_ADDR']), file_get_contents($this->dir . 'banned_ips')))
            die($this->ban_message);
        $this->exec_ban = str_replace('{IP}', $_SERVER['REMOTE_ADDR'], $this->exec_ban);
        $this->debug = $debug;
        if(!function_exists('sys_getloadavg'))
        {
            function sys_getloadavg()
            {
                return array(0,0,0);
            }
        }
        $this->load = sys_getloadavg();
        if(!$this->sbots())
        {
            $this->attack = true;
            $f = fopen($this->dir . ip2long($_SERVER["REMOTE_ADDR"]), "a");
            fwrite($f, "query\n");
            fclose($f);
        }
    }

    /**
     * Старт работы антиддоса
     **/
    function start()
    {
        if($this->attack == false)
            return;
        switch($this->ddos)
        {
            case 1:
                $this->addos1();
                break;
            case 2:
                $this->addos2();
                break;
            case 3:
                $this->ddosuser = substr(ip2long($_SERVER['REMOTE_ADDR']), 0, 4);
                $this->ddospass = substr(ip2long($_SERVER['REMOTE_ADDR']), 4, strlen(ip2long($_SERVER['REMOTE_ADDR'])));
                $this->addos3();
                break;
            case 4:
                die($this->off_message);
                break;
            case 5:
                if ($this->load[0] > $this->maxload)
                {
                    header('HTTP/1.1 503 Too busy, try again later');
                    die('<center><h1>503 Server too busy.</h1></center><hr><small><i>Server too busy. Please try again later. Apache server on ' . $_SERVER['HTTP_HOST'] . ' at port 80 with <a href="http://forum.xaknet.ru/">ddos protect</a></i></small>');
                }
                break;
            default:
                break;
        }
        if ($_COOKIE['ddos'] == $this->indeficator)
            @unlink($this->dir . ip2long($_SERVER["REMOTE_ADDR"]));
    }

    /**
     * Функция проверяет не является ли клиент поисковым ботом
     **/
    function sbots()
    {
        $tmp = array();
        foreach($this->searchbots as $bot)
        {
            $tmp[] = strpos(gethostbyaddr($_SERVER['REMOTE_ADDR']), $bot) !== false;
            if($tmp[count($tmp) - 1] == true)
            {
                $this->is_bot = true;
                break;
            }
        }
        return $this->is_bot;
    }

    /**
     * Функция бана
     **/
    private function ban()
    {
        if (! system($this->exec_ban))
        {
            $f = fopen($this->dir . 'banned_ips', "a");
            fwrite($f, ip2long($_SERVER['REMOTE_ADDR']) . '|');
            fclose($f);
        }
        die($this->ban_message);
    }
    /**
     * Первый тип защиты
     **/
    function addos1()
    {
        if (empty($_COOKIE['ddos']) or !isset($_COOKIE['ddos']))
        {
            $counter = @file($this->dir . ip2long($_SERVER["REMOTE_ADDR"]));
            setcookie('ddos', $this->indeficator, time() + 3600 * 24 * 7 * 356); // ставим куки на год.
            if (count($counter) > 10) {
                if (! $this->debug)
                    $this->ban();
                else
                    die("Блокированы.");
            }
            if (! $_COOKIE['ddos_log'] == '1')
            {
                if (! $_GET['antiddos'] == 1)
                {
                    setcookie('ddos_log', '1', time() + 3600 * 24 * 7 * 356); //чтоб не перекидывало постоянно рефрешем.
                    if(headers_sent())
                        die('Header already sended, check it, line '.__LINE__);
                    header("Location: ./?antiddos=1");
                }
            }
        } elseif ($_COOKIE['ddos'] !== $this->indeficator)
        {
            if (! $this->debug)
                $this->ban();
            else
                die("Блокированы.");
        }
    }

    /**
     * Второй тип защиты
     **/
    function addos2()
    {
        if (empty($_COOKIE['ddos']) or $_COOKIE['ddos'] !== $this->indeficator)
        {
            if (empty($_GET['antiddos']))
            {
                if (! $_COOKIE['ddos_log'] == '1')
                    //проверям есть ли запись в куках что был запрос
                    die('<meta http-equiv="refresh" content="0;URL=?antiddos=' . $this->indeficator . '" />');
            } elseif ($_GET['antiddos'] == $this->indeficator)
            {
                setcookie('ddos', $this->indeficator, time() + 3600 * 24 * 7 * 356);
                setcookie('ddos_log', '1', time() + 3600 * 24 * 7 * 356); //типо запрос уже был чтоб не перекидывало постоянно рефрешем.
            }
            else
            {
                if (!$this->debug)
                    $this->ban();
                else
                {
                    echo "May be shall not transform address line?";
                    die("Блокированы.");
                }
            }
        }
    }

    /**
     * Третий тип защиты
     **/
    function addos3()
    {
        if (! isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== $this->ddosuser || $_SERVER['PHP_AUTH_PW'] !== $this->ddospass)
        {
            header('WWW-Authenticate: Basic realm="Vvedite parol\':  ' . $this->ddospass . ' | Login: ' . $this->ddosuser . '"');
            header('HTTP/1.0 401 Unauthorized');
            if (! $this->debug)
                $this->ban();
            else
                die("Блокированы.");
            die("<h1>401 Unauthorized</h1>");
        }
    }
}
/*
// Exmaple
$ad = new antiDdos(false);
$ad->dir = 'bots/';
$ad->ddos = 2;
$ad->start();
*/
?>