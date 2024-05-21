<?php

namespace Gzhegow\ErrorBag;

interface ErrorBagInterface extends \Countable, \IteratorAggregate
{
    public function count() : int;

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable;
}
