<?php

require_once __DIR__ . '/error-bag.php';


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
        $b->warning($bb, [ 'aaa', $i ], 'tag_aaa');
        // то же самое, только в актуальный (текущий)
        // _error_bag_warning($bb, [ 'aaa', $i ], 'tag_aaa');

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

        $b->warning($bb, [ 'aaaa', $i ], 'tag_aaaa');
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
        $b->warning("Warning {$i}", null, 'tag3'); // 3
    }

    // > принимаем решение в текущей функции, если нужно
    // if (! $b->hasErrors()) {
    // if (! $b->hasWarnings()) {
    if (! $b->isEmpty()) {
        return null;
    }

    return $i;
}


function main()
{
    // > включаем отлов ошибок
    _error_bag($e);


    $result = a();
    // var_dump($result); // > какой-то результат вашей логики


    // var_dump($e->toArray($implodeKeySeparator = '.')); // > все проблемы массивом
    // var_dump($e->getErrors()->toArray('.')); // > все ошибки массивом
    // var_dump($e->getWarnings()->toArray('.')); // > все предупреждения массивом

    // var_dump($e->toArrayNested($asObject = true)); // > все проблемы вложенным массивом


    $ee = $e->getByTags($andTags = [ 'tag_aaa', 'tag1' ]);
    if (! (6 === count($ee))) throw new \RuntimeException();

    $ee = $e->getByTags($tag = 'tag1', $orTag = 'tag2');
    if (! (18 === count($ee))) throw new \RuntimeException();
    
    $ee = $e->getByTags($andTags = [ 'tag1', 'tag2' ]);
    if (! (0 === count($ee))) throw new \RuntimeException();

    $ee = $e->getByTags($andTags = (object) [ 'tag_aaa', 'tag1' ], $orAndTags = (object) [ 'tag_aaa', 'tag2' ]);
    if (! (18 === count($ee))) throw new \RuntimeException();


    $ee = $e->getByPath($path = [ 'aaa', 1 ]);
    if (! (5 === count($ee))) throw new \RuntimeException();

    $ee = $e->getByPath($path = [ 'aaa', 1 ], $orPath = [ 'aaa', 2 ]);
    if (! (10 === count($ee))) throw new \RuntimeException();

    $ee = $e->getByPath($andPathes = (object) [ [ 'aaa', 1 ], [ 'aaa', 2 ] ]);
    if (! (0 === count($ee))) throw new \RuntimeException();    
    
    $ee = $e->getByPath($andPathes = (object) [ [ 'aaa', 1 ], [ 'aaaa', 1 ] ], $orAndPathes = (object) [ [ 'aaa', 1 ], [ 'aaaa', 2 ] ]);
    if (! (2 === count($ee))) throw new \RuntimeException();


    // > завершаем отлов ошибок, иначе следующая функция продолжит отлавливать в объявленный ранее error-bag
    _error_bag_end($e);

    $stack = ErrorBagStack::getInstance();
    if (! (null === $stack->hasErrorBag())) throw new \RuntimeException();
}


main();
