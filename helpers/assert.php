<?php

namespace Gzhegow\ErrorBag;


function _filter_str($value) : ?string
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

function _filter_string($value) : ?string
{
    if (null === ($_value = _filter_str($value))) {
        return null;
    }

    if ('' === $_value) {
        return null;
    }

    return $_value;
}
