<?php

namespace Gzhegow\ErrorBag;

use Gzhegow\ErrorBag\Struct\ErrorBagItem;


class ErrorBagFactory implements ErrorBagFactoryInterface
{
    public function newErrorBagStack() : ErrorBagStackInterface
    {
        return new ErrorBagStack($this);
    }

    public function newErrorBagPool() : ErrorBagPoolInterface
    {
        return new ErrorBagPool();
    }

    public function newErrorBagItem() : ErrorBagItem
    {
        return new ErrorBagItem();
    }
}
