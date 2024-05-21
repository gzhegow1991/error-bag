<?php

namespace Gzhegow\ErrorBag;


function _array_path($path, ...$pathes) : array
{
    $result = [];

    $array = [ $path, $pathes ];

    array_walk_recursive($array, function ($value) use (&$result) {
        if (null !== $value) {
            $result[] = _filter_str($value);
        }
    });

    return $result;
}

/**
 * @throws \LogicException|\RuntimeException
 */
function &_array_put_path(array &$dst, $path, $value) // : &mixed
{
    $fullpath = _array_path($path);

    if (! $fullpath) {
        throw _php_throw(
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
                . _php_dump($p)
                . ' / ' . _php_dump($path)
            );
        }
    }

    $ref = $value;

    return $ref;
}

/**
 * @throws \LogicException|\RuntimeException
 */
function _array_set_path(array &$dst, $path, $value) : void
{
    _array_put_path($dst, $path, $value);
}
