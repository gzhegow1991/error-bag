<?php

use Gzhegow\ErrorBag\Lib;
use Gzhegow\ErrorBag\ErrorBag;
use Gzhegow\ErrorBag\ErrorBagFactory;


require_once __DIR__ . '/vendor/autoload.php';


// > настраиваем PHP
ini_set('memory_limit', '32M');

// > настраиваем обработку ошибок
error_reporting(E_ALL);
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (error_reporting() & $errno) {
        throw new \ErrorException($errstr, -1, $errno, $errfile, $errline);
    }
});
set_exception_handler(function ($e) {
    var_dump(Lib::php_dump($e));
    var_dump($e->getMessage());
    var_dump(($e->getFile() ?? '{file}') . ': ' . ($e->getLine() ?? '{line}'));

    die();
});


class TestException extends \Exception
{
}


function a()
{
    // > получаем текущий пул (родительский, для читабельности лучше указывать это в самом начале функции)
    ErrorBag::get($b);

    // > создаем дочерний пул, который будет отвечать за функцию aa() (передаем по ссылке переменную для краткости написания)
    ErrorBag::begin($bb);

    // > вызываем aa()
    $result = aa();

    // > закрываем дочерний пул (опционально - передаем его самого для проверки не забыли ли закрыть другие глубже по коду)
    ErrorBag::end($verify = $bb);

    // > соединяем ошибки из закрытого с присвоением пути и тегов, для читабельности лучше использовать $b, $bb, $bbb и так далее
    $b->merge($bb, $path = [ 'aa' ], $tags = [ 'tag_aa' ]);
    // $b->mergeAsErrors($bb, $path = [ 'aa' ], $tags = [ 'tag_aa' ]); // > соединить и преобразовать типы в Ошибка
    // $b->mergeAsMessages($bb, $path = [ 'aa' ], $tags = [ 'tag_aa' ]); // > соединить и преобразовать типы в Сообщение
    // $b->mergeErrors($bb, $path = [ 'aa' ], $tags = [ 'tag_aa' ]); // > соединить только ошибки
    // $b->mergeMessages($bb, $path = [ 'aa' ], $tags = [ 'tag_aa' ]); // > соединить только сообщения

    return $result;
}

function aa()
{
    ErrorBag::get($b);

    $result = [];

    for ( $i = 0; $i <= 5; $i++ ) {
        ErrorBag::begin($bb);

        $_result = [];
        try {
            $_result = aaa();
        }
        catch ( TestException $e ) {
            // > завершаем все пулы из стека до того, который нам известен
            $bbb = ErrorBag::capture($until = $bb);

            // > из-за кода в этом примере, который не использует генераторы, мы не можем определить номер итерации, на которой выброшено исключение, значит укажем путь [ 'aaaa', '-1' ]
            $bb->message($bbb, [ 'aaaa', -1 ], 'tag_aaaa');

            // > по желанию можно добавить ошибку из исключения
            // $bb->error($e->getMessage());
        }

        ErrorBag::end($bb);

        // > соединяем закрытый пул со сменой типа на message (case: ошибка была решена) с присвоением пути и тегов
        // > выполнит то же, что и $b->mergeAsMessages(), поскольку на входе пул
        $b->message($bb, [ 'aaa', $i ], 'tag_aaa');

        // > если во вложенном были ошибки - элемент пропускаем (принятие решение в родителе в зависимости от потомка)
        if ($bb->hasErrors()) {
            continue;
        }

        $result[] = $_result;
    }

    return $result;
}

/**
 * @throws TestException
 */
function aaa()
{
    ErrorBag::get($b);

    $result = [];

    for ( $i = 0; $i <= 5; $i++ ) {
        ErrorBag::begin($bb);

        $_result = aaaa($i);

        if ($i === 5) {
            // > бросаем исключение, таким образом ::end() + ::merge() не выполнится
            // > в блоке catch() мы используем ::capture(), чтобы завершить все пулы, которые из-за исключения завершить не удалось
            // > ps. я пробовал использовать __destruct() и контексты, чтобы делать это автоматически
            // > но php garbage collector и работа с zval refcount приводят к очистке объектов группами, и от этого больше проблем, чем пользы
            throw new TestException('My Exception');
        }

        ErrorBag::end($bb);

        $b->message($bb, [ 'aaaa', $i ], 'tag_aaaa');

        if ($bb->hasErrors()) {
            continue;
        }

        $result[] = $_result;
    }

    return $result;
}

function aaaa($i)
{
    ErrorBag::get($b);

    if (in_array($i, [ 0, 1, 2 ])) {
        // > добавляем ошибку, можно указать путь и теги
        $b->error("[ Tag 1 ] {$i}", $path = $i, $tags = 'tag1'); // 1

    } elseif (in_array($i, [ 3, 4 ])) {
        // > добавляем ошибку, можно указать путь и теги
        $b->message("[ Tag 2 ] {$i}", $path = $i, $tags = 'tag2'); // 2, 4

    } elseif ($i === 5) {
        // > добавляем предупреждение, можно указать путь и теги
        $b->message("[ Tag 3 ] {$i}", $path = $i, $tags = 'tag3'); // 3
    }

    // > принимаем решение в текущей функции, если нужно
    // if ($b->hasItems()) {
    // if ($b->hasErrors()) {
    // if ($b->hasMessages()) {
    if (! $b->isEmpty()) {
        return null;
    }

    return $i;
}


// > Настройка модуля
// > можно расширить класс и написать свою фабрику
$factory = new ErrorBagFactory();
$root = ErrorBag::getInstance($factory);

// > можно null передать или ничего, само создаст фабрику по-умолчанию
// $factory = null;
// $root = ErrorBag::getInstance();

// > Сброс стека пулов перед использованием, или если мы не знаем, пуст ли стек пулов на текущий момент
// $stackLatest = ErrorBag::reset();
// > Позже можно вернуть сброшенный стек обратно
// ErrorBag::reset($stackLatest);

// > Создаем новый пул
ErrorBag::begin($b);

// > Запускаем произвольный код
$result = a();

// > Выводим или сохраняем в хранилище
// var_dump($b->toArray($implodeKeySeparator = '|')); // > все проблемы массивом
// var_dump($b->toArrayNested($asObject = true)); // > все проблемы вложенным массивом

// var_dump($b->getErrors()->toArray($implodeKeySeparator = '|')); // > все ошибки массивом
// var_dump($b->getMessages()->toArray($implodeKeySeparator = '|')); // > все сообщения массивом
// var_dump($b->toErrors()->toArray($implodeKeySeparator = '|')); // > преобразовать всё в ошибки, затем все ошибки массивом
// var_dump($b->toMessages()->toArray($implodeKeySeparator = '|')); // > преобразовать всё в сообщения, затем все сообщения массивом

// > Завершаем пул
ErrorBag::end($b);


// > Немного тестов:

if (! (null === $root->getStack()->current())) throw new \RuntimeException();
echo 'Test OK' . PHP_EOL;

$bb = $b->getByTags(
    $tag = 'tag1',  // даст 18
    $orTag = 'tag2' // и ещё 12
);
if (! (30 === count($bb))) throw new \RuntimeException(); // 5/6
echo 'Test OK' . PHP_EOL;

$bb = $b->getByTags($andTags = [
    'tag_aaa', // даст 36
    'tag1', // но тут только 18
]);
if (! (18 === count($bb))) throw new \RuntimeException(); // 3/6
echo 'Test OK' . PHP_EOL;

$bb = $b->getByTags($andTags = [
    'tag1', // даст 18
    'tag2', // но тут только 0
]);
if (! (0 === count($bb))) throw new \RuntimeException(); // 0/6
echo 'Test OK' . PHP_EOL;

$bb = $b->getByTags(
    $andTags = (object) [ 'tag_aaa', 'tag1' ],  // 18
    $orAndTags = (object) [ 'tag_aaa', 'tag2' ] // and 12
);
if (! (30 === count($bb))) throw new \RuntimeException(); // 5/6
echo 'Test OK' . PHP_EOL;


$bb = $b->getByPath($path = [ 'aaa', 1 ]);               // 6
if (! (6 === count($bb))) throw new \RuntimeException(); // 1/6
echo 'Test OK' . PHP_EOL;

$bb = $b->getByPath(
    $path = [ 'aaa', 1 ],  // даст 6
    $orPath = [ 'aaa', 2 ] // и ещё 6
);
if (! (12 === count($bb))) throw new \RuntimeException(); // 2/6
echo 'Test OK' . PHP_EOL;

$bb = $b->getByPath(
    $andPathes = (object) [
        [ 'aaa', 1 ], // даст 6
        [ 'aaa', 2 ], // но тут только 0
    ]
);
if (! (0 === count($bb))) throw new \RuntimeException(); // 0/6
echo 'Test OK' . PHP_EOL;

$bb = $b->getByPath(
    $andPathes = (object) [
        [ 'aaa', 1 ], // даст 6
        [ 'aaaa', 1 ], // но тут только 1
    ],
    $orAndPathes = (object) [
        [ 'aaa', 1 ], // даст 6
        [ 'aaaa', 2 ], // но тут только 1
    ]
);
if (! (2 === count($bb))) throw new \RuntimeException(); // 2/36
echo 'Test OK' . PHP_EOL;

$bb = $b->getByPath([ 'aaaa', -1 ]);                     // даст 6
if (! (6 === count($bb))) throw new \RuntimeException(); // 1/6
echo 'Test OK' . PHP_EOL;
