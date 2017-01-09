<head>
<title>Host Tester v0.3.1</title>
<style type="text/css">
body {
background:url(//se1.yapcdn.net/1/DnTawFC.jpg) no-repeat left top;
margin:0px;
}

#cent {
width: 740px;
margin: 20px auto 50px auto;
background: rgba(255, 255, 255, 0.84);
padding: 37px;
border: 1px solid rgba(0, 0, 0, 1);
box-shadow: 0px 0px 10px;
}

table {
margin: 10px 55px;
}

input#p {
margin:0px 3px;
width:400px;
}
</style>
</head>
<body>
<div id="cent">
<div class="pol">
<form action="test.php">
<div id="proc">
<p><input type="checkbox" name="cpu_test" checked>Тестировать CPU</p>
<p><input type="checkbox" name="mysql_test">Тестировать MySQL</p>
</div>
<table>
<tr>
<td>Хост:</td>
<td><input name="mysql_host" type="text" value="" size="36"></td></tr>
<tr>
<td>Пользователь:</td>
<td><input name="mysql_user" type="text" value="" size="36"></td></tr>
<tr>
<td>Пароль:</td>
<td><input name="mysql_pass" type="password" value="" size="36"></td></tr>
<tr>
<td>Имя базы:</td>
<td><input name="mysql_bd" type="text" value="" size="36"></td></tr>
</table>
<div id="proc">
<p><input type="checkbox" name="filesystem_test" checked>Тестирование файловой системы</p>
</div>
<input type="submit" value="Начать тестирование">

</form>
</div>
</div>
<div style="bottom:0;width: 380px;font-family: Tahoma;font-size: 12px;background: #ECECEC;padding: 11px 50px 11px 30px;border-top-right-radius: 32px;
left:0;
position:fixed;
z-index:99999;">
Скрипт написал <a href="http://x2k.ru/" style="text-decoration:none;">XaKeP</a> специально для <a href="http://obzor.ly" style="text-decoration:none;">obzor.ly</a>
</div>
</body>