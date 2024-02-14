<?php

require_once __DIR__ . '/error-bag.php';


function a()
{
    _error_bag($e);

    _error_bag_push($ee);
    $result = aa();
    _error_bag_pop();

    _error_bag_merge($ee, 'aa', 'tag_aa');

    return $result;
}

function aa()
{
    _error_bag($e);

    $result = [];

    for ( $i = 0; $i <= 5; $i++ ) {
        _error_bag_push($ee);
        $_result = aaa();
        _error_bag_pop();

        _error_bag_warning($ee, [ 'aaa', $i ], 'tag_aaa');
        if ($ee->isErrors()) {
            continue;
        }

        $result[] = $_result;
    }

    return $result;
}

function aaa()
{
    _error_bag($e);

    $result = [];

    for ( $i = 0; $i <= 5; $i++ ) {
        _error_bag_push($ee);
        $_result = aaaa($i);
        _error_bag_pop();

        _error_bag_warning($ee, [ 'aaaa', $i ], 'tag_aaaa');
        if ($ee->isErrors()) {
            continue;
        }

        $result[] = $_result;
    }

    return $result;
}

function aaaa($i)
{
    if (! $i) {
        return $i; // 0

    }
    if ($i === 1) {
        _error_bag_error("Error {$i}", null, 'tag1'); // 1

    } elseif ($i % 2) {
        _error_bag_error("Error {$i}", null, 'tag2'); // 2, 4

    } elseif ($i % 3) {
        _error_bag_warning("Warning {$i}", null, 'tag3'); // 3
    }

    return null;
}


function main()
{
    // > включаем отлов ошибок, если эту функцию не вызвать - остальные будут возвращать null-обьекты
    _error_bag_start($e);


    $result = a();
    // var_dump($result); // > какой-то результат вашей логики


    // var_dump($e->toArray($implodeKeySeparator = '.')); // > все ошибки массивом
    // var_dump($e->toArrayNested($asObject = true)); // > все ошибки вложенным массивом


    // $ee = $e->getByTags($andTags = [ 'tag_aaa', 'tag1' ]);
    // var_dump($ee->toArray()); // 6 результатов

    // $ee = $e->getByTags($andTags = [ 'tag1', 'tag2' ]);
    // var_dump($ee->toArray()); // 0 результатов

    // $ee = $e->getByTags($tag = 'tag1', $orTag = 'tag2');
    // var_dump($ee->toArray()); // 18 результатов

    // $ee = $e->getByTags($tags = (object) [ 'tag_aaa', 'tag1' ], $orTags = (object) [ 'tag_aaa', 'tag2' ]);
    // var_dump($ee->toArray()); // 18 результатов


    // $ee = $e->getByPath($path = [ 'aaa', 1 ]);
    // var_dump($ee->toArray()); // 5 результатов

    // $ee = $e->getByPath($path = [ 'aaa', 1 ], $orPath = [ 'aaa', 2 ]);
    // var_dump($ee->toArray()); // 10 результатов

    // $ee = $e->getByPath($andPathes = (object) [ [ 'aaa', 1 ], [ 'aaaa', 1 ] ]);
    // var_dump($ee->toArray()); // 1 результат


    // $ee = $e->getByPath($path = [ 'aaa', 1 ]);
    // var_dump($ee->toArray()); // 4 результата

    // $ee = $e->getByPath($path = [ 'aaa', 1 ], $orPath = [ 'aaaa', 1 ]);
    // var_dump($ee->toArray()); // 1 результат

    // $ee = $e->getByPath($path = [ 'aaa', 1 ], $orPath = [ 'aaa', 2 ]);
    // var_dump($ee->toArray()); // 0 результатов    


    // > завершаем отлов ошибок, иначе следующая функция продолжит отлавливать в объявленный выше error-bag
    _error_bag_end();

    var_dump(ErrorBagStack::getInstance()); // empty stack
}


main();
