<?php

require_once __DIR__ . '/error-bag.php';


function a()
{
    // > получаем текущий error-bag
    _error_bag($e);

    // > создаем дочерний error-bag, который будет отвечать за функцию aa()
    _error_bag_push($ee);
    
    $result = aa();
    
    // > завершаем дочерний error-bag (опционально - передаем его самого для проверки, не забыли ли глубже закрыть другие)
    _error_bag_pop($ee);

    // > соединяем закрытый в текущий с присвоением пути и тегов
    $e->merge($ee, 'aa', 'tag_aa');

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

        // > соединяем закрытый в текущий как предупреждения (если ошибка была решена) с присвоением пути и тегов
        $e->warning($ee, [ 'aaa', $i ], 'tag_aaa');
        
        // > если во вложенном были ошибки - элемент пропускаем (принятие решение в родителе в зависимости от потомка)
        if ($ee->hasErrors()) {
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

        $e->warning($ee, [ 'aaaa', $i ], 'tag_aaaa');
        if ($ee->hasErrors()) {
            continue;
        }

        $result[] = $_result;
    }

    return $result;
}

function aaaa($i)
{
    _error_bag($e);

    if ($i === 1) {
        // > добавляем ошибку, можно указать путь и теги
        $e->error("Error {$i}", $path = null, $tags = 'tag1'); // 1

    } elseif ($i % 2) {
        $e->error("Error {$i}", null, 'tag2'); // 2, 4

    } elseif ($i % 3) {
        // > добавляем предупреждение, можно указать путь и теги
        $e->warning("Warning {$i}", null, 'tag3'); // 3
    }
    
    // > принимаем решение в текущей функции, если нужно
    // if (! $e->hasErrors()) {
    // if (! $e->hasWarnings()) {
    if (! $e->isEmpty()) {
        return null;
    }

    return $i;
}


function main()
{
    // > включаем отлов ошибок, если эту функцию не вызвать - остальные будут возвращать null-обьекты
    _error_bag_start($e);


    $result = a();
    // var_dump($result); // > какой-то результат вашей логики


    // var_dump($e->toArray($implodeKeySeparator = '.')); // > все проблемы массивом
    // var_dump($e->getErrors()->toArray('.')); // > все ошибки массивом
    // var_dump($e->getWarnings()->toArray('.')); // > все предупреждения массивом

    // var_dump($e->toArrayNested($asObject = true)); // > все проблемы вложенным массивом


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


    // > завершаем отлов ошибок, иначе следующая функция продолжит отлавливать в объявленный ранее error-bag
    _error_bag_end($e);
    // var_dump(ErrorBagStack::getInstance()); // empty stack
}


main();
