<?php

namespace Gzhegow\ErrorBag;


/**
 * > возвращает актуальный error-bag
 * > или создает новый и делает его актуальным
 */
function error_bag(ErrorBag &$current = null) : ErrorBag
{
    $current = null;

    $stack = ErrorBagStack::getInstance();

    if (! $current = $stack->hasErrorBag()) {
        $_current = new ErrorBag();

        $stack->pushErrorBag($_current);

        $current = $_current;
    }

    return $current;
}

/** > получает текущий error-bag, если он есть */
function error_bag_current() : ?ErrorBag
{
    $new = null;

    $stack = ErrorBagStack::getInstance();

    $current = $stack->hasErrorBag();

    return $current;
}


/**
 * > создает и возвращает новый error-bag, делает его актуальным
 */
function error_bag_push(ErrorBag &$new = null) : ErrorBag
{
    $new = null;

    $stack = ErrorBagStack::getInstance();

    $current = $stack->pushErrorBag();

    $new = $current;

    return $new;
}

/**
 * > забирает актуальный error-bag, если он был, делает его родителя актуальным
 * > если указан $verify, то когда последний не равен переданному, выбросит исключение
 */
function error_bag_pop(?ErrorBag $verify) : ErrorBag
{
    $stack = ErrorBagStack::getInstance();

    $last = $stack->popErrorBag($verify);

    $last = $last ?? new ErrorBag();

    return $last;
}


/**
 * > создает и возвращает новый error-bag, делает его актуальным
 */
function error_bag_start(ErrorBag &$new = null) : ErrorBag
{
    return error_bag_push($new);
}

/**
 * > завершает несколько error-bag
 * > если указан $until, то завершает до указанного error-bag
 * > возвращает объединение всех error-bag, которые были завершены в виде нового error-bag
 */
function error_bag_end(ErrorBag $until = null) : ErrorBag
{
    $stack = ErrorBagStack::getInstance();

    $flush = $stack->endErrorBag($until);

    return $flush;
}


/**
 * > оборачивает вызов функции в error-bag
 */
function error_bag_call(?ErrorBag &$b, callable $fn, array $args)
{
    error_bag_push($b);

    $result = call_user_func_array($fn, $args);

    error_bag_pop($b);

    return $result;
}


/**
 * > поскольку путь до элемента всегда массив, который будет соединен в момент ->toArray($separator)
 * > необходим метод который позволит собрать из _path(string, int, \Stringable, array) строку
 * > аргументы соединяются через `:`, тогда как вложенные массивы через `.`, null приводится к строке ''
 * > что нельзя привести к строке вызовет исключение
 */
function error_bag_path(...$path) : string
{
    $result = [];

    foreach ( $path as $p ) {
        $p = [ $p ];

        $implode = [];
        array_walk_recursive($p, static function ($string) use (&$implode) {
            $implode[] = _assert_string($string);
        });
        $implode = implode('.', $implode);

        $result[] = $implode;
    }

    $result = implode(':', $result);

    return $result;
}


function error_bag_message($message, $path = null, $tags = null) : void
{
    error_bag($e);

    $e->message($message, $path, $tags);
}

function _error_bag_error($error, $path = null, $tags = null) : void
{
    error_bag($e);

    $e->error($error, $path, $tags);
}

function error_bag_merge($errorBag, $path = null, $tags = null) : void
{
    error_bag($e);

    $e->merge($errorBag, $path, $tags);
}
