<?php

namespace Gzhegow\ErrorBag;


/**
 * > gzhegow, выводит короткую форму содержимого переменной в виде строки
 */
function _php_dump($value) : string
{
    if (! is_iterable($value)) {
        $_value = null
            ?? (($value === null) ? '{ NULL }' : null)
            ?? (($value === false) ? '{ FALSE }' : null)
            ?? (($value === true) ? '{ TRUE }' : null)
            ?? (is_object($value) ? ('{ object(' . get_class($value) . ' # ' . spl_object_id($value) . ') }') : null)
            ?? (is_resource($value) ? ('{ resource(' . gettype($value) . ' # ' . ((int) $value) . ') }') : null)
            //
            ?? (is_int($value) ? (var_export($value, 1)) : null) // INF
            ?? (is_float($value) ? (var_export($value, 1)) : null) // NAN
            ?? (is_string($value) ? ('"' . $value . '"') : null)
            //
            ?? null;

    } else {
        foreach ( $value as $k => $v ) {
            $value[ $k ] = null
                ?? (is_array($v) ? '{ array(' . count($v) . ') }' : null)
                ?? (is_iterable($v) ? '{ iterable(' . get_class($value) . ' # ' . spl_object_id($value) . ') }' : null)
                ?? _php_dump($v);
        }

        $_value = var_export($value, true);

        $_value = str_replace("\n", ' ', $_value);
        $_value = preg_replace('/\s+/', ' ', $_value);
    }

    if (null === $_value) {
        throw _php_throw(
            [ 'Unable to dump variable', $value ]
        );
    }

    return $_value;
}

/**
 * > gzhegow, перебрасывает исключение на "тихое", если из библиотеки внутреннее постоянно подсвечивает весь код
 *
 * @param string|array|\LogicException $error
 *
 * @return \LogicException|null
 */
function _php_throw($error, $code = null, $previous = null) : ?object
{
    if (is_a($error, \LogicException::class)) {
        return $error;
    }

    if (is_resource($error)) {
        throw new \LogicException(
            'The `error` is not supported: '
            . _php_dump($error)
        );
    }

    $isCustomMessage =
        is_array($error)
        || is_object($error)
        || ((string) $error != $error);

    if ($isCustomMessage) {
        $error = (array) $error;
        $error = vsprintf($error[ 0 ], array_slice($error, 1));
    }

    $throwClass = \LogicException::class;

    return new $throwClass($error, $code, $previous);
}
