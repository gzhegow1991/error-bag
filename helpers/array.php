<?php

namespace Gzhegow\ErrorBag;


function _array_path($path, ...$pathes) : array
{
    $result = [];

    $array = [ $path, $pathes ];

    array_walk_recursive($array, function ($value) use (&$result) {
        if (null !== $value) {
            $result[] = _filter_string($value);
        }
    });

    return $result;
}

/**
 * @throws \RuntimeException
 */
function &_array_put(array &$dst, $path, $value) // : &mixed
{
    $fullpath = _array_path($path);

    if (! $fullpath) {
        throw new \LogicException(
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
 * @throws \RuntimeException
 */
function _array_set(array &$dst, $path, $value) // : mixed
{
    // > gzhegow, array_put returns reference, this function returns value

    $ref = _array_put($dst, $path, $value);

    return $ref;
}
