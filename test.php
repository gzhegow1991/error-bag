<?php

require_once __DIR__ . '/vendor/autoload.php';


// > настраиваем PHP
ini_set('memory_limit', '32M');


// > настраиваем обработку ошибок
(new \Gzhegow\Lib\Exception\ErrorHandler())
    ->useErrorReporting()
    ->useErrorHandler()
    ->useExceptionHandler()
;


// > добавляем несколько функция для тестирования
function _debug(...$values) : string
{
    $lines = [];
    foreach ( $values as $value ) {
        $lines[] = \Gzhegow\Lib\Lib::debug()->type_id($value);
    }

    $ret = implode(' | ', $lines) . PHP_EOL;

    echo $ret;

    return $ret;
}

function _dump(...$values) : string
{
    $lines = [];
    foreach ( $values as $value ) {
        $lines[] = \Gzhegow\Lib\Lib::debug()->value($value);
    }

    $ret = implode(' | ', $lines) . PHP_EOL;

    echo $ret;

    return $ret;
}

function _dump_array($value, int $maxLevel = null, bool $multiline = false) : string
{
    $content = $multiline
        ? \Gzhegow\Lib\Lib::debug()->array_multiline($value, $maxLevel)
        : \Gzhegow\Lib\Lib::debug()->array($value, $maxLevel);

    $ret = $content . PHP_EOL;

    echo $ret;

    return $ret;
}

function _assert_output(
    \Closure $fn, string $expect = null
) : void
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

    \Gzhegow\Lib\Lib::assert()->output($trace, $fn, $expect);
}

function _assert_microtime(
    \Closure $fn, float $expectMax = null, float $expectMin = null
) : void
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

    \Gzhegow\Lib\Lib::assert()->microtime($trace, $fn, $expectMax, $expectMin);
}


// >>> ЗАПУСКАЕМ!

class TestException extends \Exception
{
}


function a()
{
    // > получаем текущий пул (родительский, для читабельности лучше указывать это в самом начале функции)
    \Gzhegow\ErrorBag\ErrorBag::get($b);

    // > создаем дочерний пул, который будет отвечать за функцию aa() (передаем по ссылке переменную для краткости написания)
    \Gzhegow\ErrorBag\ErrorBag::begin($bb);

    // > вызываем aa()
    $result = aa();

    // > закрываем дочерний пул (опционально - передаем его самого для проверки не забыли ли закрыть другие глубже по коду)
    \Gzhegow\ErrorBag\ErrorBag::end($verify = $bb);

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
    \Gzhegow\ErrorBag\ErrorBag::get($b);

    $result = [];

    for ( $i = 0; $i <= 5; $i++ ) {
        \Gzhegow\ErrorBag\ErrorBag::begin($bb);

        $_result = [];
        try {
            $_result = aaa();
        }
        catch ( TestException $e ) {
            // > завершаем все пулы из стека до того, который нам известен
            $bbb = \Gzhegow\ErrorBag\ErrorBag::capture($until = $bb);

            // > из-за кода в этом примере, который не использует генераторы, мы не можем определить номер итерации, на которой выброшено исключение, значит укажем путь [ 'aaaa', '-1' ]
            $bb->message($bbb, [ 'aaaa', -1 ], 'tag_aaaa');

            // > по желанию можно добавить ошибку из исключения
            // $bb->error($e->getMessage());
        }

        \Gzhegow\ErrorBag\ErrorBag::end($bb);

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
    \Gzhegow\ErrorBag\ErrorBag::get($b);

    $result = [];

    for ( $i = 0; $i <= 5; $i++ ) {
        \Gzhegow\ErrorBag\ErrorBag::begin($bb);

        $_result = aaaa($i);

        if ($i === 5) {
            // > бросаем исключение, таким образом ::end() + ::merge() не выполнится
            // > в блоке catch() мы используем ::capture(), чтобы завершить все пулы, которые из-за исключения завершить не удалось
            // > ps. я пробовал использовать __destruct() и контексты, чтобы делать это автоматически
            // > но php garbage collector и работа с zval refcount приводят к очистке объектов группами, и от этого больше проблем, чем пользы
            throw new TestException('My Exception');
        }

        \Gzhegow\ErrorBag\ErrorBag::end($bb);

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
    \Gzhegow\ErrorBag\ErrorBag::get($b);

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


// > сначала всегда фабрика
$factory = new \Gzhegow\ErrorBag\ErrorBagFactory();

// > создаем стэк
$stack = new \Gzhegow\ErrorBag\ErrorBagStack(
    $factory
);

// > создаем фасад
$facade = new \Gzhegow\ErrorBag\ErrorBagFacade(
    $factory,
    $stack
);

// > сохраняем фасад статически
\Gzhegow\ErrorBag\ErrorBag::setFacade($facade);


// > Сброс стека пулов перед использованием, или если мы не знаем, пуст ли стек пулов на текущий момент
// $stackLatest = ErrorBag::reset();

// > Позже можно вернуть сброшенный стек обратно
// ErrorBag::reset($stackLatest);


// > TEST
// > так можно искать маршруты с помощью имен или тегов
$b = null;
$fn = function () use (&$b) {
    _dump('TEST 1');

    // > создаем новый пул
    \Gzhegow\ErrorBag\ErrorBag::begin($b);

    // > запускаем произвольный код
    a();

    _dump($b->toArray($implodeKeySeparator = '|')); // > все проблемы массивом
    _dump($b->toArrayNested($asObject = true));     // > все проблемы вложенным массивом

    _dump($b->getErrors()->toArray($implodeKeySeparator = '|'));   // > все ошибки массивом
    _dump($b->getMessages()->toArray($implodeKeySeparator = '|')); // > все сообщения массивом

    _dump($b->toErrors()->toArray($implodeKeySeparator = '|'));    // > преобразовать всё в ошибки, затем все ошибки массивом
    _dump($b->toMessages()->toArray($implodeKeySeparator = '|'));  // > преобразовать всё в сообщения, затем все сообщения массивом

    // > завершаем пул
    \Gzhegow\ErrorBag\ErrorBag::end($b);

    echo '';
};
_assert_output($fn, '
"TEST 1"
[ "ERR" => "{ array(0) }", "MSG" => "{ array(36) }" ]
[ "ERR" => "{ array(0) }", "MSG" => "{ array(1) }" ]
[ "ERR" => "{ array(0) }", "MSG" => "{ array(0) }" ]
[ "ERR" => "{ array(0) }", "MSG" => "{ array(36) }" ]
[ "ERR" => "{ array(36) }", "MSG" => "{ array(0) }" ]
[ "ERR" => "{ array(0) }", "MSG" => "{ array(36) }" ]
');


// > TEST
// > так можно искать маршруты с помощью имен или тегов
$fn = function () use (&$b) {
    _dump('TEST 2');

    $bb = $b->getByTags(
        $tag = 'tag1',  // 18
        $orTag = 'tag2' // +12
    );
    _dump(count($bb)); // 30

    $bb = $b->getByTags(
        $andTags = [
            'tag_aaa', // 36
            'tag1', // -18
        ]);
    _dump(count($bb)); // 18

    $bb = $b->getByTags(
        $andTags = [
            'tag1', // 18
            'tag2', // -18
        ]);
    _dump(count($bb)); // 0

    $bb = $b->getByTags(
        $andTags = (object) [ 'tag_aaa', 'tag1' ],  // 18
        $orAndTags = (object) [ 'tag_aaa', 'tag2' ] // +12
    );
    _dump(count($bb)); // 30

    $bb = $b->getByPath(
        $path = [ 'aaa', 1 ]
    );
    _dump(count($bb)); // 6

    $bb = $b->getByPath(
        $path = [ 'aaa', 1 ],  // 6
        $orPath = [ 'aaa', 2 ] // +6
    );
    _dump(count($bb)); // 12

    $bb = $b->getByPath(
        $andPathes = (object) [
            [ 'aaa', 1 ], // 6
            [ 'aaa', 2 ], // -6
        ]
    );
    _dump(count($bb)); // 0

    $bb = $b->getByPath(
        $andPathes = (object) [
            [ 'aaa', 1 ], // 6
            [ 'aaaa', 1 ], // -5
        ],
        $orAndPathes = (object) [
            [ 'aaa', 1 ], // 6
            [ 'aaaa', 2 ], // -5
        ]
    );
    _dump(count($bb)); // 2

    $bb = $b->getByPath(
        $path = [ 'aaaa', -1 ] // 6
    );
    _dump(count($bb)); // 6

    echo '';
};
_assert_output($fn, '
"TEST 2"
30
18
0
30
6
12
0
2
6
');
