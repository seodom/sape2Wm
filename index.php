<?php
/**
 * Скрипт для вывода баланса sape.ru на webmoney при достижении необходимой суммы
 *
 * @author Odarchenko N.D. <odarchenko.n.d@gmail.com>
 * @created 07.01.13 11:44
 *
 */
// Запускать по cron, желательно с интервалом не чаще 2 раз в сутки


// Минимальная сумма на счету, при которой выводить
$minSum = 1100;
// Сколько выводить
$sum = 1000; //Нельзя выводить меньше 150 рублей

// Данные для входа в аккаунт sape.ru
$login = 'webmaster1000';
$password = '';

// Название файла для cookies (доступен для записи)
$cookieFile = 'secretFileCookie_09824.tmp';

//////////////////////////////////////// Настройки закончены, дальше скрипт все сделает ////////////////////////////////

header('Content-Type: text/html;charset=UTF-8');

if (!extension_loaded("curl"))
{ // ссылка ведет на запрос в google "как установить curl"
    die('<a href="http://goo.gl/ncGxw">cURL</a> extension is not available');
}

// Чистим файл
@file_put_contents($cookieFile, '');

if (!file_exists($cookieFile))
{
    die('cookie file not exists! check your config.');
}


// Проверяем что его удалось перезаписать
if (file_get_contents($cookieFile) != '')
{ // ссылка ведет на запрос в google "как установить права доступа 777 на папку"
    die('cookie file <a href="http://goo.gl/3yCCM">not writable</a>!');
}

require 'sapeCurl.php';
$s = new sapeCurl($cookieFile);
// Авторизуемся на сайте
$s->login($login, $password);

$money = $s->getBalance();

if ($money === FALSE)
{
    die('Error with getBalance! Check your script code');
}

echo 'Money: ', $money, '<br>';

if ($money >= $minSum)
{
    $ok = $s->makeRequest4Payment($sum);
    $error = $s->getLastError();
    if ('' == $error && $ok) //no errors
    {
        echo '<span class="success">OK</span>';
    }
    else // ошибка
    {
        echo $error;

        // тут может быть отправка уведомления или логирование ошибок
    }
}
else
{
    echo 'Nothing to do.';
}

// Чистим файл с куками в целях безопасности
@file_put_contents($cookieFile, '');