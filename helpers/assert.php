<?php

namespace Gzhegow\ErrorBag;


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
