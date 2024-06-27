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
            $_value = [];
            foreach ( $value as $k => $v ) {
                $_value[ $k ] = null
                    ?? (is_array($v) ? '{ array(' . count($v) . ') }' : null)
                    // ! recursion
                    ?? static::php_dump($v, $maxlen);
            }

            ob_start();
            var_dump($_value);
            $_value = ob_get_clean();

            if (is_object($value)) {
                $_value = '{ iterable(' . get_class($value) . ' # ' . spl_object_id($value) . '): ' . $_value . ' }';
            }

            $_value = trim($_value);
            $_value = preg_replace('/\s+/', ' ', $_value);
        }

        if (null === $_value) {
            throw static::php_throwable(
                'Unable to dump variable'
            );
        }

        return $_value;
    }

    /**
     * > gzhegow, перебрасывает исключение на "тихое", если из библиотеки внутреннее постоянно подсвечивается в PHPStorm
     *
     * @return \LogicException|\RuntimeException|null
     */
    public static function php_throwable($error = null, ...$errors) : ?object
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

        $throwErrors = static::php_throwable_args($error, ...$errors);

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
     *     messageList: string[],
     *     odeList: int[],
     *     previousList: string[],
     *     messageCodeList: array[],
     *     messageDataList: array[],
     *     messageObjectList: object[],
     *     message: ?string,
     *     code: ?int,
     *     previous: ?string,
     *     messageCode: ?string,
     *     messageData: ?array,
     *     messageObject: ?object,
     *     unresolved: array,
     * }
     */
    public static function php_throwable_args($arg = null, ...$args) : array
    {
        array_unshift($args, $arg);

        $len = count($args);

        $messageList = null;
        $codeList = null;
        $previousList = null;
        $messageCodeList = null;
        $messageDataList = null;
        $messageObjectList = null;

        $message = null;
        $code = null;
        $previous = null;
        $messageCode = null;
        $messageData = null;
        $messageObject = null;

        $unresolved = [];

        for ( $i = 0; $i < $len; $i++ ) {
            $a = $args[ $i ];

            if (is_int($a)) {
                $code = $codeList[ $i ] = $a;

                continue;
            }

            if (is_a($a, \Throwable::class)) {
                $previous = $previousList[ $i ] = $a;

                continue;
            }

            if (null !== ($vString = static::filter_string($a))) {
                $message = $messageList[ $i ] = $vString;

                continue;
            }

            if (
                is_array($a)
                || is_a($a, \stdClass::class)
            ) {
                $messageData = $messageDataList[ $i ] = (array) $a;

                continue;
            }

            $unresolved[ $i ] = $a;
        }

        for ( $i = $len - 1; $i >= 0; $i-- ) {
            if (isset($messageDataList[ $i ][ 0 ])) {
                if ($messageString = static::filter_string($messageDataList[ $i ][ 0 ])) {
                    $message = $messageList[ $i ] = $messageString;
                }
            }

            if (isset($messageList[ $i ])) {
                if (null !== $messageList[ $i ]) {
                    if (preg_match('/^[a-z](?!.*\s)/i', $messageList[ $i ])) {
                        $messageCode = $messageCodeList[ $i ] = strtoupper($messageList[ $i ]);
                    }
                }
            }

            if (null !== $messageDataList[ $i ]) {
                $messageObject = $messageObjectList[ $i ] = (object) $messageDataList[ $i ];

            } elseif (null !== $messageList[ $i ]) {
                $messageObject = $messageObjectList[ $i ] = (object) [ $messageList[ $i ] ];
            }

            if (null !== $messageDataList[ $i ]) {
                unset($messageDataList[ $i ][ 0 ]);

                if (empty($messageDataList[ $i ])) {
                    $messageDataList[ $i ] = null;
                }
            }
        }

        $result = [];

        $result[ 'messageList' ] = $messageList;
        $result[ 'codeList' ] = $codeList;
        $result[ 'previousList' ] = $previousList;
        $result[ 'messageDataList' ] = $messageDataList;
        $result[ 'messageObjectList' ] = $messageObjectList;

        $result[ 'message' ] = $message;
        $result[ 'code' ] = $code;
        $result[ 'previous' ] = $previous;
        $result[ 'messageData' ] = $messageData;
        $result[ 'messageObject' ] = $messageObject;

        $result[ 'unresolved' ] = $unresolved;

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
            throw static::php_throwable(
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

                throw static::php_throwable(
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
