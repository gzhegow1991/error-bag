<?php

namespace Gzhegow\ErrorBag;

class Lib
{
    /**
     * > gzhegow, выводит короткую и наглядную форму содержимого переменной в виде строки
     */
    public static function php_dump($value, int $maxlen = null) : string
    {
        if (is_string($value)) {
            $_value = ''
                . '{ '
                . 'string(' . strlen($value) . ')'
                . ' "'
                . ($maxlen
                    ? (substr($value, 0, $maxlen) . '...')
                    : $value
                )
                . '"'
                . ' }';

        } elseif (! is_iterable($value)) {
            $_value = null
                ?? (($value === null) ? '{ NULL }' : null)
                ?? (($value === false) ? '{ FALSE }' : null)
                ?? (($value === true) ? '{ TRUE }' : null)
                ?? (is_object($value) ? ('{ object(' . get_class($value) . ' # ' . spl_object_id($value) . ') }') : null)
                ?? (is_resource($value) ? ('{ resource(' . gettype($value) . ' # ' . ((int) $value) . ') }') : null)
                //
                ?? (is_int($value) ? (var_export($value, 1)) : null) // INF
                ?? (is_float($value) ? (var_export($value, 1)) : null) // NAN
                //
                ?? null;

        } else {
            foreach ( $value as $k => $v ) {
                $value[ $k ] = null
                    ?? (is_array($v) ? '{ array(' . count($v) . ') }' : null)
                    ?? (is_iterable($v) ? '{ iterable(' . get_class($value) . ' # ' . spl_object_id($value) . ') }' : null)
                    // ! recursion
                    ?? static::php_dump($v, $maxlen);
            }

            $_value = var_export($value, true);

            $_value = str_replace("\n", ' ', $_value);
            $_value = preg_replace('/\s+/', ' ', $_value);
        }

        if (null === $_value) {
            throw static::php_throw(
                'Unable to dump variable'
            );
        }

        return $_value;
    }

    /**
     * > gzhegow, перебрасывает исключение на "тихое", если из библиотеки внутреннее постоянно подсвечивается в PHPStorm
     *
     * @return \LogicException|null
     */
    public static function php_throw($error = null, ...$errors) : ?object
    {
        if (is_a($error, \Closure::class)) {
            $error = $error(...$errors);
        }

        if (
            is_a($error, \LogicException::class)
            || is_a($error, \RuntimeException::class)
        ) {
            return $error;
        }

        $throwErrors = static::php_throw_errors($error, ...$errors);

        $message = $throwErrors[ 'message' ] ?? __FUNCTION__;
        $code = $throwErrors[ 'code' ] ?? -1;
        $previous = $throwErrors[ 'previous' ] ?? null;

        return $previous
            ? new \RuntimeException($message, $code, $previous)
            : new \LogicException($message, $code);
    }

    /**
     * > gzhegow, парсит ошибки для передачи результата в конструктор исключения
     *
     * @return array{
     *     message: string,
     *     code: int,
     *     previous: string,
     *     messageData: array,
     *     messageObject: object,
     * }
     */
    public static function php_throw_errors($error = null, ...$errors) : array
    {
        $_message = null;
        $_code = null;
        $_previous = null;
        $_messageData = null;
        $_messageObject = null;

        array_unshift($errors, $error);

        foreach ( $errors as $err ) {
            if (is_int($err)) {
                $_code = $err;

                continue;
            }

            if (is_a($err, \Throwable::class)) {
                $_previous = $err;

                continue;
            }

            if (null !== ($_string = static::filter_string($err))) {
                $_message = $_string;

                continue;
            }

            if (
                is_array($err)
                || is_a($err, \stdClass::class)
            ) {
                $_messageData = (array) $err;

                if (isset($_messageData[ 0 ])) {
                    $_message = static::filter_string($_messageData[ 0 ]);
                }
            }
        }

        $_message = $_message ?? null;
        $_code = $_code ?? null;
        $_previous = $_previous ?? null;

        $_messageObject = null
            ?? ((null !== $_messageData) ? (object) $_messageData : null)
            ?? ((null !== $_message) ? (object) [ $_message ] : null);

        if (null !== $_messageData) {
            unset($_messageData[ 0 ]);

            $_messageData = $_messageData ?: null;
        }

        $result = [];
        $result[ 'message' ] = $_message;
        $result[ 'code' ] = $_code;
        $result[ 'previous' ] = $_previous;
        $result[ 'messageData' ] = $_messageData;
        $result[ 'messageObject' ] = $_messageObject;

        return $result;
    }


    public static function filter_str($value) : ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (
            null === $value
            || is_array($value)
            || is_resource($value)
        ) {
            return null;
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                $_value = (string) $value;

                return $_value;
            }

            return null;
        }

        $_value = $value;
        $status = @settype($_value, 'string');

        if ($status) {
            return $_value;
        }

        return null;
    }

    public static function filter_string($value) : ?string
    {
        if (null === ($_value = static::filter_str($value))) {
            return null;
        }

        if ('' === $_value) {
            return null;
        }

        return $_value;
    }


    public static function array_path($path, ...$pathes) : array
    {
        $result = [];

        $array = [ $path, $pathes ];

        array_walk_recursive($array, function ($value) use (&$result) {
            if (null !== $value) {
                $result[] = static::filter_str($value);
            }
        });

        return $result;
    }

    /**
     * @throws \LogicException|\RuntimeException
     */
    public static function &array_put_path(array &$dst, $path, $value) // : &mixed
    {
        $fullpath = static::array_path($path);

        if (! $fullpath) {
            throw static::php_throw(
                'Unable to ' . __FUNCTION__ . ' due to empty path'
            );
        }

        $ref =& $dst;

        while ( null !== key($fullpath) ) {
            $p = array_shift($fullpath);

            if (! array_key_exists($p, $ref)) {
                $ref[ $p ] = $fullpath
                    ? []
                    : null;
            }

            $ref =& $ref[ $p ];

            if ((! is_array($ref)) && $fullpath) {
                unset($ref);
                $ref = null;

                throw new \RuntimeException(
                    "Trying to traverse scalar value: "
                    . static::php_dump($p)
                    . ' / ' . static::php_dump($path)
                );
            }
        }

        $ref = $value;

        return $ref;
    }

    /**
     * @throws \LogicException|\RuntimeException
     */
    public static function array_set_path(array &$dst, $path, $value) : void
    {
        static::array_put_path($dst, $path, $value);
    }
}
