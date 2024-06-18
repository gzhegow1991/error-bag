<?php

namespace Gzhegow\ErrorBag;

interface ErrorBagStackInterface
{
    public function current() : ?ErrorBagPoolInterface;


    public function has(ErrorBagPoolInterface $pool) : ?ErrorBagPoolInterface;

    public function get(ErrorBagPoolInterface $pool) : ErrorBagPoolInterface;


    public function push(ErrorBagPoolInterface $new) : ErrorBagStackInterface;

    public function pop(?ErrorBagPoolInterface $verify) : ErrorBagPoolInterface;

    public function flush(ErrorBagPoolInterface $until = null) : ErrorBagPoolInterface;
}
