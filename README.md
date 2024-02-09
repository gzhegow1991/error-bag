# ErrorBag

Все языки программирования отталкиваются от исключений в качестве сбора ошибок.
Сильнейший плюс исключений в том, что исключение останавливает работу программы. В этом же сильнейший минус.

Бывает что вы хотите остановить не все логику программы, а только её часть. Если в логике выпадет исключение, она остановится целиком.
Вы не сможете вернуться внутрь прерванной исключением функции, чтобы принять меры и продолжить её выполнение, она для вас потеряна.
Это заставит вас дробить ваши функции на микроскопические до такой степени, что у вас будет больше функций, чем кода.

Современное сообщество считает это "правильным подходом к программированию".

Мне жаль, но правильный путь - это написание того, что работает, а не красиво выглядит.

Вторая задача - это возможность собрать ошибки всего скрипта в единую кучу данных, сохраняя вложенность и возможность поиска ошибок, возможность их копирования из блока в блок, если действия выполняются независимо.

Этот инструмент должен вам в этом помочь. Получите текущий ящик из стека, наполните его, примите решение. В родительской функции оберните, чтобы извлечь, и полученное можете соединить с текущей, залогировать или отправить. А собрав все - можете залпом вывести в АПИ.

Обычно, когда я предлагаю идею в PHP сообщество, проходит 2 года и потом её внедряют как чью-то ещё. Сегодня 10.02.2024.

```
function a()
{
    _error_bag($e);

    _error_bag_push($ee);
    $result = aa();
    _error_bag_pop();

    _error_bag_merge($ee, [ 'aa' ]);

    return $result;
}

function aa()
{
    _error_bag($e);

    $result = [];

    for ( $i = 0; $i < 5; $i++ ) {
        _error_bag_push($ee);
        $_result = aaa();
        _error_bag_pop();

        _error_bag_warning($ee, [ 'aaa', $i ], [ 'tag_aaa' ]);
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

    for ( $i = 0; $i < 5; $i++ ) {
        _error_bag_push($ee);
        $_result = aaaa($i);
        _error_bag_pop();

        _error_bag_warning($ee, [ 'aaaa', $i ]);
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
        return $i;

    } elseif ($i % 2) {
        _error_bag_warning("Warning {$i}", null, [ 'tag1' ]);

    } else {
        _error_bag_error("Error {$i}", null, [ 'tag2' ]);
    }

    return null;
}


_error_bag($e);

$result = a();

// var_dump($result);


var_dump($e->toArray());
var_dump($e->toArrayNested());


$ee = $e;
// $ee = $e->getByPath([ 'aaa', 1 ]);                 // 4 results
// $ee = $e->getByPath([ 'aaa', 1 ], [ 'aaaa', 1 ]);  // 1 result
// $ee = $e->getByPath([ 'aaa', 1 ], [ 'aaa', 2 ]);   // 0 results
// $ee = $e->getByTags([ 'tag1' ]);                   // 10 results
// $ee = $e->getByTags([ 'tag1', 'tag_aaa' ]);        // 10 results
// $ee = $e->getByTags([ 'tag1', 'tag2' ]);           // 0 results
var_dump($ee->toArray());
var_dump($ee->toArrayNested());
