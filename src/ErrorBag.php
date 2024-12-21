<?php

namespace Gzhegow\ErrorBag;


use Gzhegow\ErrorBag\Struct\ErrorBagItem;


class ErrorBag
{
    const TYPE_ERR = 'ERR';
    const TYPE_MSG = 'MSG';

    const LIST_TYPE = [
        self::TYPE_ERR => true,
        self::TYPE_MSG => true,
    ];


    public static function newErrorBagItem() : ErrorBagItem
    {
        return static::$facade->newErrorBagItem();
    }


    public static function reset(ErrorBagStackInterface $previous = null) : ErrorBagStackInterface
    {
        return static::$facade->reset($previous);
    }


    public static function begin(?ErrorBagPoolInterface &$new_) : ErrorBagPoolInterface
    {
        return static::$facade->begin($new_);
    }

    public static function get(ErrorBagPoolInterface &$current_ = null) : ErrorBagPoolInterface
    {
        return static::$facade->get($current_);
    }

    public static function end(?ErrorBagPoolInterface $verify) : ErrorBagPoolInterface
    {
        return static::$facade->end($verify);
    }

    public static function capture(ErrorBagPoolInterface $until) : ErrorBagPoolInterface
    {
        return static::$facade->capture($until);
    }

    public static function flush() : ErrorBagPoolInterface
    {
        return static::$facade->flush();
    }

    public static function current() : ErrorBagPoolInterface
    {
        return static::$facade->current();
    }


    public static function setFacade(ErrorBagFacadeInterface $facade) : ?ErrorBagFacadeInterface
    {
        $last = static::$facade;

        static::$facade = $facade;

        return $last;
    }

    /**
     * @var ErrorBagFacadeInterface
     */
    protected static $facade;
}
