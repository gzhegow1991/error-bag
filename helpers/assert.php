<?php

namespace Gzhegow\ErrorBag;


function _assert($error, callable $fn, array $args)
{
    try {
        $result = call_user_func_array($fn, $args);
    }
    catch ( \Throwable $e ) {
        throw _assert_throw($error, $e->getCode(), $e->getPrevious());
    }

    return $result;
}

/**
 * @param mixed $value
 *
 * @return string
 */
function _assert_type($value) : string
{
    $_value = null
        ?? (($value === null) ? '{ NULL }' : null)
        ?? (($value === false) ? '{ FALSE }' : null)
        ?? (($value === true) ? '{ TRUE }' : null)
        ?? (is_object($value) ? ('{ object(' . get_class($value) . ' # ' . spl_object_id($value) . ') }') : null)
        ?? (is_resource($value) ? ('{ resource(' . gettype($value) . ' # ' . ((int) $value) . ') }') : null)
        ?? '{ ' . gettype($value) . ' }';

    return $_value;
}

/**
 * @param mixed $value
 *
 * @return string
 */
function _assert_dump($value) : string
{
    $_value = null;

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
                ?? _assert_dump($v);
        }

        $_value = var_export($value, true);

        $_value = str_replace("\n", ' ', $_value);
        $_value = preg_replace('/\s+/', ' ', $_value);
    }

    if (null === $_value) {
        throw _assert_throw(
            [ 'Unable to dump variable', $value ]
        );
    }

    return $_value;
}

/**
 * @param string|array|\LogicException|\RuntimeException $error
 *
 * @return \LogicException|\RuntimeException|null
 */
function _assert_throw($error, $code = null, $previous = null) : ?object
{
    $_error = null
        ?? (is_a($error, \LogicException::class) ? $error : null)
        ?? (is_a($error, \RuntimeException::class) ? $error : null)
        ?? (is_string($error) ? new \LogicException($error, $code, $previous) : null)
        ?? (is_array($error)
            ? new \LogicException(
                array_shift($error) . ":"
                . "\n" . implode("\n| ", array_map('_assert_dump', $error))
            )
            : null
        );

    if (null === $_error) {
        throw new \LogicException([
            'The `error` should be: string|array|\LogicException|\RuntimeException',
            $error,
        ]);
    }

    return $_error;
}


function _filter_string($value) : ?string
{
    if (is_string($value)) {
        return $value;
    }

    if (is_array($value)) {
        return null;
    }

    if (! is_object($value)) {
        $_value = $value;
        $status = @settype($_value, 'string');

        if ($status) {
            return $_value;
        }

    } elseif (method_exists($value, '__toString')) {
        $_value = (string) $value;

        return $_value;
    }

    return null;
}

function _assert_string($value) : ?string
{
    if (null === $value) return null;

    if (null === ($filterResult = _filter_string($value))) {
        throw _assert_throw(
            [ 'The `value` should be string', $value ]
        );
    }

    return $filterResult;
}
