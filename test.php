<?php

use Gzhegow\ErrorBag\ErrorBagStack;
use function Gzhegow\ErrorBag\_error_bag;
use function Gzhegow\ErrorBag\_error_bag_pop;
use function Gzhegow\ErrorBag\_error_bag_end;
use function Gzhegow\ErrorBag\_error_bag_push;


require_once __DIR__ . '/vendor/autoload.php';


function a()
{
    // > получаем текущий error-bag
    _error_bag($b);

    // > создаем дочерний error-bag, который будет отвечать за функцию aa()
    _error_bag_push($bb);

    $result = aa();

    // > завершаем дочерний error-bag (опционально - передаем его самого для проверки, не забыли ли глубже закрыть другие)
    _error_bag_pop($bb);

    // > соединяем закрытый в указанный с присвоением пути и тегов
    $b->merge($bb, 'aa', 'tag_aa');

    // > то же самое, только объединение произойдет в актуальный (текущий)
    // _error_bag_merge($bb, 'aa', 'tag_aa');

    return $result;
}

function aa()
{
    _error_bag($b);

    $result = [];

    for ( $i = 0; $i <= 5; $i++ ) {
        _error_bag_push($bb);

        $_result = aaa();

        _error_bag_pop($bb);

        // > соединяем закрытый в указанный как предупреждения (если ошибка была решена) с присвоением пути и тегов
        $b->message($bb, [ 'aaa', $i ], 'tag_aaa');

        // то же самое, только в актуальный (текущий)
        // _error_bag_message($bb, [ 'aaa', $i ], 'tag_aaa');

        // > если во вложенном были ошибки - элемент пропускаем (принятие решение в родителе в зависимости от потомка)
        if ($bb->hasErrors()) {
            continue;
        }

        $result[] = $_result;
    }

    return $result;
}

function aaa()
{
    _error_bag($b);

    $result = [];

    for ( $i = 0; $i <= 5; $i++ ) {
        _error_bag_push($bb);

        $_result = aaaa($i);

        _error_bag_pop($bb);

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
    _error_bag($b);

    if ($i === 1) {
        // > добавляем ошибку, можно указать путь и теги
        $b->error("Error {$i}", $path = null, $tags = 'tag1'); // 1

    } elseif ($i % 2) {
        $b->error("Error {$i}", null, 'tag2'); // 2, 4

    } elseif ($i % 3) {
        // > добавляем предупреждение, можно указать путь и теги
        $b->message("Message {$i}", null, 'tag3'); // 3
    }

    // > принимаем решение в текущей функции, если нужно
    // if (! $b->hasErrors()) {
    // if (! $b->hasMessages()) {
    if (! $b->isEmpty()) {
        return null;
    }

    return $i;
}


function main()
{
    // > включаем отлов ошибок
    _error_bag($b);

    $result = a();
    var_dump($result); // > какой-то результат вашей логики

    // > завершаем отлов ошибок, иначе дальнейший код продолжит отлавливать в открытый ранее error-bag
    _error_bag_end($b);


    // var_dump($b->toArrayNested($asObject = true)); // > все проблемы вложенным массивом    

    var_dump($b->toArray($implodeKeySeparator = '|')); // > все проблемы массивом
    // var_dump($b->getErrors()->toArray('|')); // > все ошибки массивом
    // var_dump($b->getMessages()->toArray('|')); // > все сообщения массивом


    $stack = ErrorBagStack::getInstance();
    if (! (null === $stack->hasErrorBag())) throw new \RuntimeException();
    echo 'Test OK' . PHP_EOL;

    $bb = $b->getByTags($andTags = [ 'tag_aaa', 'tag1' ]);
    if (! (6 === count($bb))) throw new \RuntimeException();
    echo 'Test OK' . PHP_EOL;

    $bb = $b->getByTags($tag = 'tag1', $orTag = 'tag2');
    if (! (18 === count($bb))) throw new \RuntimeException();
    echo 'Test OK' . PHP_EOL;

    $bb = $b->getByTags($andTags = [ 'tag1', 'tag2' ]);
    if (! (0 === count($bb))) throw new \RuntimeException();
    echo 'Test OK' . PHP_EOL;

    $bb = $b->getByTags($andTags = (object) [ 'tag_aaa', 'tag1' ], $orAndTags = (object) [ 'tag_aaa', 'tag2' ]);
    if (! (18 === count($bb))) throw new \RuntimeException();
    echo 'Test OK' . PHP_EOL;


    $bb = $b->getByPath($path = [ 'aaa', 1 ]);
    if (! (5 === count($bb))) throw new \RuntimeException();
    echo 'Test OK' . PHP_EOL;

    $bb = $b->getByPath($path = [ 'aaa', 1 ], $orPath = [ 'aaa', 2 ]);
    if (! (10 === count($bb))) throw new \RuntimeException();
    echo 'Test OK' . PHP_EOL;

    $bb = $b->getByPath($andPathes = (object) [ [ 'aaa', 1 ], [ 'aaa', 2 ] ]);
    if (! (0 === count($bb))) throw new \RuntimeException();
    echo 'Test OK' . PHP_EOL;

    $bb = $b->getByPath($andPathes = (object) [ [ 'aaa', 1 ], [ 'aaaa', 1 ] ], $orAndPathes = (object) [ [ 'aaa', 1 ], [ 'aaaa', 2 ] ]);
    if (! (2 === count($bb))) throw new \RuntimeException();
    echo 'Test OK' . PHP_EOL;
}


main();
