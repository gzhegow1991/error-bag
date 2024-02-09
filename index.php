<?php

require_once __DIR__ . '/error-bag.php';

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
