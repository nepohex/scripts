<?php
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding("UTF-8");
set_time_limit(0);
error_reporting(E_ALL);
#------------------------------------------------------------------ШАБЛОН
echo '
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8" />
	<title>LiReg - массовый регистратор сайтов в статистике LiveInternet</title>
	 <link rel="shortcut icon" href="li.ico">
<style>
* { margin: 0; padding: 0; outline: 0; }
body {
    font-size: 12px;
    line-height: 22px;
    font-family: verdana, arial, sans-serif;
    color: #727272;
}
.fform {
padding-top: 10px;
width: 100%;
border-radius: 5px;
background: rgba(255,255,255,0.5);
-webkit-box-shadow: 0px -1px 47px -14px rgba(0,0,0,1);
-moz-box-shadow: 0px -1px 47px -14px rgba(0,0,0,1);
box-shadow: 0px -1px 47px -14px rgba(0,0,0,1);
}

.fform input[type="text"],select{
  padding-left: 3px;  
  border:1px gray solid;color:#39494a;
  height: 35px;
  margin: 0 0 10px 10px;
  font-size: 16px;
  width: 250px;
}

.fform input:focus {
outline: 0;
}


.fform input[type="submit"]{
  cursor: pointer;  
  width: 252px;
  padding: 0;
  height: 50px;
  background: #00BFF2;  
  color: #fff;
  font-size: 20px;
  border: none;
  border-bottom: 3px solid #0099CF;
  font-family: \'DinPro\';
  -webkit-box-shadow: 0px 12px 40px -11px rgba(0,0,0,0.75);
  -moz-box-shadow: 0px 12px 40px -11px rgba(0,0,0,0.75);
  box-shadow: 0px 12px 40px -11px rgba(0,0,0,0.75);
}

 
.fform textarea {
  
  height: 180px;
  width: 90%;
  margin: 0 0 10px 10px;
  font-size: 16px;
  padding:5px;
  background-color: #fff;
  background-position: 3% center;
  margin-top: 10px; 
  border: 1px solid #39494a;
}
.s_text 


	</style>
           

</head>
<body>
<center><a href="index.php"><img src="logo.jpg" /></a></center>
<p><br /></p>

<div class="fform"  style="margin:0 auto; width:800px;padding:10px 10px 10px 30px;">
';

#------------------------------------------------------------------РЕГИСТРАЦИЯ
if (isset($_POST['submit'])){

if (empty($_POST['sites'] ) ) die('<h1>Так пусто внутри</h1></div><center>(c) 2015 <a href="http://sanchopancho.ru" target="_blank">SanchoPancho.ru</a></center></body></html>');
$sites=explode("\n",$_POST['sites']);
foreach ($sites as $value)
{
        $e=explode(';',trim($value));
        if (empty($e[0])) break;
        $domen=urlencode($e[0]);
        
        #е-мэйл и пароль по умолчанию
        $email='mail%40'.$domen;
        $pass='123'; 
        
        #общий пароль и мыло
        if (!empty($_POST['pass'] ) ) $pass=urlencode($_POST['pass']);
        if (!empty($_POST['email'] ) )  $email=urlencode($_POST['email']);
        
        #частный случай
        if (!empty($e[1] ) ) $pass=urlencode($e[1]);
        if (!empty($e[2] ) )  $email=urlencode($e[2]);
        
        
        #сначала берем рандомное число
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,'http://www.liveinternet.ru/add');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT,  'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.13 (KHTML, like Gecko) Chrome/9.0.597.47 Safari/534.13');
        $content = curl_exec($ch);
        curl_close($ch);
        if (!preg_match('#random value="(\d+)"#Ui',$content,$random))
        #вдруг не получили страницу, пробуем еще разок
        {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,'http://www.liveinternet.ru/add');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT,  'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.13 (KHTML, like Gecko) Chrome/9.0.597.47 Safari/534.13');
        $content = curl_exec($ch);
        curl_close($ch);
        }
        if (!preg_match('#random value="(\d+)"#Ui',$content,$random)) {die ('<font color="?\#990000"?><b>Ошибка: нет Интернета или что-то сломалось в скрипте</b></font><br>Ваш список сайтов: <Br><textarea>'.$_POST['sites'].'</textarea>');}
          
$postdata='random='.$random[1].'&rules=agreed&type=site&nick='.$domen.'&url=http%3A%2F%2F'.$domen.'%2F&name='.$domen.'&email='.$email.'&password='.$pass.'&check='.$pass.'&keywords=&aliases=&language=ru&group=&private=on&subscribe=off&www=&confirmed=+%D0%B7%D0%B0%D1%80%D0%B5%D0%B3%D0% B8%D1%81%D1%82%D1%80%D0%B8%D1%80%D0%BE';
#&ok=+%D0%B4%D0%B0%D0%BB%D1%8C%D1%88%D0%B5+%3E%3E+

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,'http://www.liveinternet.ru/add');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT,  'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.13 (KHTML, like Gecko) Chrome/9.0.597.47 Safari/534.13');
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.liveinternet.ru/add');
        curl_setopt($ch, CURLOPT_POSTFIELDS,$postdata);
        $content = curl_exec($ch);
        curl_close($ch);
        
        
        echo "<b><u>$e[0]</u></b>";
        $data=' (pass: '. urldecode($pass).' e-mail: '.urldecode($email).')';
        if (substr_count($content,'успешно зарегистрирован')>0) echo ' - <font color=green>успешно</font>';
        elseif (preg_match('#<font color="?\#990000"?><b>Ошибка: (.*)</b></font>#uiUs',$content,$err) ) echo ' - '.$err[0];
       echo $data.'<br>';
        flush();
}

}
#------------------------------------------------------------------ГЛАВНАЯ
else {

echo <<<EOF
<form action='' method='post'>
<p>При желании можно задать общий e-mail и пароль (можно оставить пустым).</p>

<input type=text name=pass value='' style=';' placeholder='Общий пароль' /> <br>
<input type=text name=email value=''  placeholder='Общий e-mail' /> 
<p>* Обязательным является только указание домена (без "HTTP" и "www") по одному на строчку. <br />
* Пароль по умолчанию - <i>123</i> E-mail - <i>mail@domen.ru</i>.</p>
<textarea placeholder="domen.ru;pass;mail@domen.ru" name="sites"></textarea>
<br /><br />

<input type="submit" value="Регистрировать &rarr;" name="submit">
</form>
EOF;
}
echo '</div><center>(c) 2015 <a href="http://sanchopancho.ru" target="_blank">SanchoPancho.ru</a></center></body></html>';