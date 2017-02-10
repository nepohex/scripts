<?php
$scripts_chain = array('status.txt');
function next_script($php_self = null, $start = null, $fin = null)
{
    global $scripts_chain;

    if ($php_self == false) {
        $php_self = $_SERVER['SCRIPT_FILENAME'];
    }
    if ($fin == true) {
        echo2("Достигли конца генерации сайта, пробуем перейти на новый круг! " . $php_self);
        return header('Location: ' . array_shift($scripts_chain));
    }
    if ($start == true) {
        echo2("Начинаем выполнять скрипт " . $php_self);
    } else {
        $i = 0;
        $php_self = array_pop(explode('/', $php_self));
        foreach ($scripts_chain as $script) {
            if ($script == $php_self) {
                echo2("Закончили со скриптом " . $_SERVER['SCRIPT_FILENAME'] . " Переходим к NEXT");
                echo2("--------------------------------$i--------------------------------");
                return header('Location: ' . $scripts_chain[$i + 1]);
            }
            $i++;
        }
        echo2("Не можем найти следующего скрипта после " . $php_self);
    }
}
//next_script(0,1);
next_script();