<head>
<title>Host Tester v0.3.1 | Results</title>
<style type="text/css">
body {
background:url(//se1.yapcdn.net/1/DnTawFC.jpg) no-repeat left top;
font-family: Calibri;
font-size: 13px;
margin:0px;
}

.test {
width: 740px;
margin: 20px auto 50px auto;
background: rgba(255, 255, 255, 0.84);
padding: 37px;
border: 1px solid rgba(0, 0, 0, 1);
box-shadow: 0px 0px 10px;
}

.test table tr td {
font-family: Comic Sans MS;
font-size: 13px;
}

h3 {
text-align: center;
font-size: 24px;
color: #0034FF;
}

</style>
</head>
<body>
<?php
$cpu_test = $_GET['cpu_test'];
$mysql_test = $_GET['mysql_test'];
if ($mysql_test == "on") {
$mysql_test=true;
$mysql_host=$_GET['mysql_host'];
$mysql_login=$_GET['mysql_user'];
$mysql_pass=$_GET['mysql_pass'];
$mysql_db=$_GET['mysql_db'];
}
else $mysql_test = false;
$filesystem_test = $_GET['filesystem_test'];

function location_info() {
	$ip = $_SERVER['SERVER_ADDR'];
	echo geoip_record_by_name($ip);
	echo geoip_org_by_name($ip);
}

function server_info() {
	$web = $_SERVER['SERVER_SOFTWARE'];
	$os = explode("(",$web);
	$os_t = php_uname();
	$web = trim($os[0]);
	if(preg_match("~lve~",$os_t) || preg_match("~.el~",$os_t)) {
		$os = shell_exec("cat /etc/issue | head -1");
		if($os == "") {
			$os = shell_exec("cat /etc/issue | head -1");
			if($os == "") {
				if(preg_match("~lve~",$os_t)) $os = "CloudLinux";
				else  $os = "CentOS";
			}
		}
	} else {
		$os = shell_exec("cat /etc/issue | head -1");
		if($os == "") {
			if(preg_match("~Apache~",$web)) {
				if($os[1] != "") {
					$os = trim(str_replace(")","",$os[1]));
				} else {
					$os = $os_t;
				}
			} else {
				$os = $os_t;
			}
		}
	}
	if(preg_match("~FreeBSD~",$os)) {
		$cpu = shell_exec("grep -w CPU: /var/run/dmesg.boot");
		$mem = shell_exec("less /var/run/dmesg.boot | grep memory");	
		$mem = explode("\n",$mem);
		$mem = trim(str_replace("real memory =","",$mem[0]));
		$mem_used = "Unknown";
	} else {
		$cpu = shell_exec("cat /proc/cpuinfo | grep 'model name'");
		if($cpu == "") { //ARM Architecture
			$cpu = shell_exec("cat /proc/cpuinfo | grep 'Processor'");
			if($cpu != "") {
				$cpuinfo = $cpu;
				$cpuinfo = trim(str_replace("Processor	:","",$cpuinfo));
				$cpus =  shell_exec("cat /proc/cpuinfo | grep 'processor'");
				$cpus = explode("\n",$cpus);
				$cpu = "";
				for($i=0;$i<count($cpus)-1;$i++) {
					$cpu .= $cpuinfo."";
				}
			} else {
				$cpu = "Unknown CPU type. Shell_exec is disabled or unknown OS";
			}
		}
		$mem = shell_exec("cat /proc/meminfo | grep -iE '(MemTotal|Cached)'");
		$mem = explode("\n",$mem);	
		$mem_cache =  trim(str_replace("Cached:","",$mem[1]));
		$mem = trim(str_replace("MemTotal:","",$mem[0]));	
		$mem = trim(str_replace("kB","",$mem));
		$mem_cache = trim(str_replace("kB","",$mem_cache));
		$mem_used = $mem - $mem_cache;
		$mem = round($mem/1024,2);
		if($mem > 1024) {
			$mem = round($mem/1024,2);
			$mem = $mem." GB";
		} else {
			$mem = $mem." MB";
		}
		$mem_used = round($mem_used/1024,2);
		if($mem_used > 1024) {
			$mem_used = round($mem_used/1024,2);
			$mem_used = $mem_used." GB";
		} else {
			$mem_used = $mem_used." MB";
		}
	}
	$cpu = explode("\n",$cpu);
	$space = disk_total_space("./");
	$space = round($space/(1024*1024*1024),0);
	$spacefree = round(disk_free_space("./")/(1024*1024*1024),0);
	$array['memfull'] = $mem;
	$array['memused'] = $mem_used;
	$array['cpu_count'] = (count($cpu) -1);
	$array['disk_space'] = $space." GB";
	$array['disk_freespace'] = $spacefree." GB";
	$cpu[0] = trim(str_replace("model name	:","",$cpu[0]));
	$array['cpu_name'] = $cpu[0];
	$array['OS'] = $os;
	$array['webserver'] = $web;
	return $array;
}

function timer($shift = false)  //Таймер
{ 
  static $first = 0; 
  static $last; 

  $now = preg_replace('#^0(.*) (.*)$#', '$2$1', microtime()); 
  if (!$first) $first = $now; 
  $res = $shift ? $now - $last : $now - $first; 
  $last = $now; 
  return $res; 
}

function pi2() { //CPU Тест часть 1
$r = 4;
	$tochnost = 20000;
	function y($x) {
		global $r;
		return sqrt($r*$r-$x*$x);
	}
	for($i=-$r*$tochnost;$i<$r*$tochnost;$i++) {
		$x1 = $i/$tochnost;
		$y1 = y($x1);
		$x2 = ($i+1)/$tochnost;
		$y2 = y($x2);
		$d += sqrt(pow(($x2-$x1),2)+pow(($y2-$y1),2));
	}
}

	echo "<div class=test><br><h3>Общие сведения</h3>".
	 "<table noborder><tr><td><img src='noimg.gif' width=350px height=1px></td></tr>";
	echo '<tr><td>Имя сервера: </td><td>http://'.$_SERVER["HTTP_HOST"].' </td></tr>'; 	
	$str =phpversion();
	echo '<tr><td>Версия PHP: </td><td>'.$str.' </td></tr>'; 
	$str =date('Y-m-d G:i:s');
	echo '<tr><td>Дата/время тестирования: </td><td>'.$str.' </td></tr>'; 

	$n=1;
timer();
if ($cpu_test == "on") { //Выбор теста CPU

//--------------------Тест1--------------------//

pi2();
$time=timer(1);
$time= round($time,4);
echo '<tr><td>CPU: Число ПИ до 20000 знака после запятой:</td><td> '.$time.' секунд.<br></td><td>';
$r = "";
$r2 = "";
$total = (1/$time)*12000;
//--------------------Тест2--------------------//

for($i=0;$i<1000000;$i++) {$c = $r.$r2;}
$time =timer(1);
$time= round($time,4);
echo '<tr><td>CPU: Миллион строк через точку:</td><td> '.$time.' секунд.<br></td><td>';
$total=$total + 1/$time * 500;
//--------------------Тест3--------------------//

for ($i=1;$i<1000000;$i++){implode("", array($r, $r2));};
$time =timer(1);
$time= round($time,4);
echo '<tr><td>CPU: млн слияний строк через массив: </td><td>'.$time.' секунд. </td></tr>'; 
$total= $total + 1/$time * 1900;
$total = round($total,0);
echo '<tr><td></td><td>Итого попугаев за CPU тест: '.$total.'</td></tr>';
$n++;
}else echo "<i>Тест пропущен</i>";

if ($mysql_test == "true") {

//---------------MySQL Тест часть 1----------------//

$link = mysql_connect($mysql_host, $mysql_login, $mysql_pass);
	if (!$link) {
		die('Невозможно соединиться: ' . mysql_error());
	}
	mysql_close($link);
	$time =timer(1);
$time= round($time,4);
	echo "<tr><td>MySQL: Соединение/сброс:</td><td> $time секунд.</td></tr>";
$total = $total + 1/$time*10;
$total1 = 1/$time*10;

//---------------MySQL Тест часть 2----------------//
$link = mysql_connect($mysql_host, $mysql_login, $mysql_pass);
	if (!$link) {
		die('Невозможно соединиться: ' . mysql_error());
	}
	mysql_select_db($mysql_db);
	$query = mysql_query('SELECT BENCHMARK(1000000, (select sin(100)))');
	$time =timer(1);
$time= round($time,4);
	echo '<tr><td>MySQL: benchmark (млн. синусов ):</td><td> '.$time.' секунд. </td></tr>'; 
	$total = $total + 1/$time*500;
$total1 = $total1 +1/$time*500;
//---------------MySQL Тест часть 3----------------//
	$query = mysql_query('drop table if exists obzor_ly',$link);
	$query = mysql_query('create table obzor_ly(a int) ENGINE=MyISAM',$link);
	for ($i=1;$i<10000;$i++){	$query = mysql_query('insert into obzor_ly values ('.$i.')');};
	$time =timer(1);
$time= round($time,4);
	echo '<tr><td>MySQL: 10000 вставок строк:</td><td> '.$time.' секунд. </td></tr>'; 
	$total = $total + 1/$time*750;
	$total1 = $total1 + 1/$time*750;
	
//---------------MySQL Тест часть 4----------------//
	mysql_select_db($mysql_db);
	$query = mysql_query('select * from obzor_ly where a>0');
	while ($row = mysql_fetch_assoc($query)) ;
	$query = mysql_query('drop table if exists obzor_ly',$link);
	$time =timer(1);
$time= round($time,4);
	echo '<tr><td>MySQL: 10000 select и fetch :</td><td>'.$time.' сек. </td></tr>'; 
	$total = $total + 1/$time;
	$total1 = $total1 + 1/$time;
	
	$total1 = round($total1,0);
	$total = round($total,0);
	echo "<tr><td></td><td>Итого попугаев за MySQL тест: $total1</td></tr>";
	echo "<tr><td></td><td>Итого попугаев за $n теста: $total</td></tr>";
$n++;
	
	//=====================================
	mysql_select_db($mysql_db);
	$query = mysql_query('select version();');
    if ($row = mysql_fetch_array($query)) {
		$str =$row[0];
		$mysqlver=$str;
		echo '<tr><td>Версия MySQL:</td><td>'.$str.' </td></tr>'; 
		echo '<input type ="hidden" name="mysql_version" value="'.$str.'">'; 	
    }
	//=====================================
	mysql_select_db($mysql_db);
	
	$query = mysql_query('show '.($mysqlver[0]=='5'?'global':'').' status like "%Uptime%";');
    $row1 = mysql_fetch_array($query); 
	$str =$row1[1];
	echo '<tr><td>Время работы сервера:</td><td>'.$str.' сек.,('.round($row1[1]/3600,2).' ч.) </td></tr>'; 
	echo '<input type ="hidden" name="mysql_uptime" value="'.$str.'">'; 
	
	
	$query = mysql_query('show  '.($mysqlver[0]=='5'?'global':'').' status like "%Bytes_sent%";');
    $row2 = mysql_fetch_array($query);
	$str =round($row2[1]/$row1[1],2);
	echo '<tr><td>Выдача байт в секунду в среднем:</td><td> '.$str.' байт ( '.$row2[1].' за весь uptime)</td></tr>'; 
	echo '<input type ="hidden" name="mysql_bytes_sended" value="'.$str.'">'; 

	$query = mysql_query('show '.($mysqlver[0]=='5'?'global':'').' status like "%Connections%";');
    $row3 = mysql_fetch_array($query);
	$str =round($row3[1]/$row1[1],5);
	echo '<tr><td>Соединений в секунду в среднем:</td><td>'.$str.' ( '.$row3[1],' за весь uptime )</td></tr>'; 
	echo '<input type ="hidden" name="mysql_connections" value="'.$str.'">'; 

	$query = mysql_query('show '.($mysqlver[0]=='5'?'global':'').' status like "%Com_select%";');
    $row4 = mysql_fetch_array($query);
	$str =round($row4[1]/$row1[1],5);
	echo '<tr><td>Запросов SELECT в секунду в среднем:</td><td>'.$str.' (  '.$row4[1].' за весь uptime)</td></tr>'; 
	echo '<input type ="hidden" name="mysql_selects" value="'.$str.'">'; 
	//=====================================
	
	mysql_close($link);
	timer(1);
	echo '<br><br>';
}

if ($filesystem_test == "on"){

	//=======================================
	$filename = 'obzor_ly.txt';
	if( !file_exists($filename)){
		$fp=fopen($filename, "w");
		fclose($fp);
	}
    if (!$handle = fopen($filename, 'w'))  die ("Не могу открыть файл ($filename) на запись");        

	for ($i=1;$i<1000000;$i++){
		if (fwrite($handle, "1") === FALSE) die ("Не могу произвести запись в файл ($filename)");
	}  
    fclose($handle);
	$time =timer(1);
$time= round($time,4);
	echo '<tr><td>FS: Запись в файл </td><td>'.$time.' сек. <br>'; 
	$total = $total + 1/$time*20000;
	$total1 = 1/$time*20000;
	//=======================================
    if (!$handle = fopen($filename, 'r'))  die ("Не могу открыть файл ($filename) на чтение");        

	while (!feof($handle)) {
		fread($handle, 1); // читаем по 1 байту
	}  
    fclose($handle);
	unlink ($filename);
	$time =timer(1);
$time= round($time,4);
	echo '<tr><td>FS: Чтение из файла </td><td>'.$time.' сек. </td></tr>'; 
	$total1 = $total1 + 1/$time*500;
	$total = $total + 1/$time*500;
	$total = round($total,0);
	$total1 = round($total1,0);
	echo "<tr><td></td><td>Итого попугаев за FS тест: $total1</td></tr>";
	echo "<tr><td></td><td>Итого попугаев за $n теста: $total</td></tr>";
}
	$info = server_info();
	echo "<tr><td>CPU name:</td><td>".$info['cpu_name'].'. </td></tr>';
	echo "<tr><td>CPU core:</td><td>".$info['cpu_count'].'. </td></tr>';
	echo "<tr><td>Full Memory:</td><td>".$info['memfull'].'. </td></tr>';
	echo "<tr><td>Used Memory:</td><td>".$info['memused'].'. </td></tr>';
	echo "<tr><td>Total disk space:</td><td>".$info['disk_space'].'. </td></tr>';
	echo "<tr><td>Total disk free space:</td><td>".$info['disk_freespace'].'. </td></tr>';
	echo "<tr><td>OS:</td><td>".$info['OS'].'. </td></tr>';
	echo "<tr><td>Webserver:</td><td>".$info['webserver'].'. </td></tr>';
	location_info();
?>
</table>
</div>
<br>
<div style="bottom:0;width: 380px;font-family: Tahoma;font-size: 12px;background: #ECECEC;padding: 11px 50px 11px 30px;border-top-right-radius: 32px;
left:0;
position:fixed;
z-index:99999;">
Скрипт написал <a href="http://x2k.ru/" style="text-decoration:none;">XaKeP</a> специально для <a href="http://host-test.ru" style="text-decoration:none;">host-test.ru</a>
</div>
</body>