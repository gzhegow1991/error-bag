<?php

namespace Gzhegow\ErrorBag;

interface ErrorBagPoolInterface extends
    \Countable,
    \IteratorAggregate
{
    public function isEmpty() : bool;


    public function hasItems() : bool;

    public function getItems() : array;


    public function hasErrors() : bool;

    public function hasMessages() : bool;


    public function getErrors() : ErrorBagPoolInterface;

    public function getMessages() : ErrorBagPoolInterface;


    public function getByPath($and, ...$orAnd) : ErrorBagPoolInterface;

    public function getByTags($and, ...$orAnd) : ErrorBagPoolInterface;


    public function errors(array $errors, $path = null, $tags = null) : ErrorBagPoolInterface;

    public function error($error, $path = null, $tags = null) : ErrorBagPoolInterface;


    public function messages(array $messages, $path = null, $tags = null) : ErrorBagPoolInterface;

    public function message($message, $path = null, $tags = null) : ErrorBagPoolInterface;


    public function merge(ErrorBagPoolInterface $pool, $path = null, $tags = null) : ErrorBagPoolInterface;

    public function mergeErrors(ErrorBagPoolInterface $pool, $path = null, $tags = null) : ErrorBagPoolInterface;

    public function mergeMessages(ErrorBagPoolInterface $pool, $path = null, $tags = null) : ErrorBagPoolInterface;

    public function mergeAsErrors(ErrorBagPoolInterface $pool, $path = null, $tags = null) : ErrorBagPoolInterface;

    public function mergeAsMessages(ErrorBagPoolInterface $pool, $path = null, $tags = null) : ErrorBagPoolInterface;


    public function toErrors() : ErrorBagPoolInterface;

    public function toMessages() : ErrorBagPoolInterface;


    public function toArray(string $implodeKeySeparator = null) : array;

    public function toArrayNested(bool $asObject = null) : array;
}
