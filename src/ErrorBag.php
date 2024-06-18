<?php

namespace Gzhegow\ErrorBag;

use Gzhegow\ErrorBag\Exception\LogicException;


class ErrorBag
{
    const TYPE_ERR = 'ERR';
    const TYPE_MSG = 'MSG';

    const LIST_TYPE = [
        self::TYPE_ERR => true,
        self::TYPE_MSG => true,
    ];


    /**
     * @var ErrorBagFactoryInterface
     */
    protected $factory;
    /**
     * @var ErrorBagStackInterface
     */
    protected $stack;


    public function __construct(
        ErrorBagFactoryInterface $factory,
        ErrorBagStackInterface $stack
    )
    {
        $this->factory = $factory;
        $this->stack = $stack;
    }


    public function getFactory() : ErrorBagFactoryInterface
    {
        return $this->factory;
    }

    public function getStack() : ErrorBagStackInterface
    {
        return $this->stack;
    }


    /**
     * > Позволяет сбросить стек пулов на другой или на пустой, возвращает текущий
     */
    public static function reset() : ErrorBagStackInterface
    {
        return static::getInstance()->doReset();
    }

    protected function doReset(ErrorBagStackInterface $previous = null) : ErrorBagStackInterface
    {
        $latest = $this->stack;

        $this->stack = $previous ?? $this->factory->newErrorBagStacK();

        return $latest;
    }


    /**
     * > Создает дочерний / новый пул
     */
    public static function begin(?ErrorBagPoolInterface &$new_) : ErrorBagPoolInterface
    {
        return static::getInstance()->doBegin($new_);
    }

    protected function doBegin(?ErrorBagPoolInterface &$new_) : ?ErrorBagPoolInterface
    {
        $new_ = null;

        $new = $this->factory->newErrorBagPool();

        $this->stack->push($new);

        $new_ = $new;

        return $new;
    }


    /**
     * > Возвращает текущий / создает новый пул
     */
    public static function get(ErrorBagPoolInterface &$new_ = null) : ErrorBagPoolInterface
    {
        return static::getInstance()->doGet($new_);
    }

    protected function doGet(ErrorBagPoolInterface &$current_ = null) : ErrorBagPoolInterface
    {
        $current_ = null;

        if (! $current = $this->stack->current()) {
            $current = $this->factory->newErrorBagPool();

            $this->stack->push($current);
        }

        $current_ = $current;

        return $current;
    }


    /**
     * > Завершает пул, если передать $verify - проверит, что пул был крайним в стеке
     * > Если не указать $until, завершит один последний пул
     */
    public static function end(?ErrorBagPoolInterface $verify) : ErrorBagPoolInterface
    {
        return static::getInstance()->doEnd($verify);
    }

    protected function doEnd(?ErrorBagPoolInterface $verify) : ErrorBagPoolInterface
    {
        $result = $this->stack->pop($verify);

        return $result;
    }


    /**
     * > Завершает стек до указанного пула, и возвращает новый пул
     * > Если не указать $until, завершит один последний пул
     */
    public static function capture(ErrorBagPoolInterface $until) : ErrorBagPoolInterface
    {
        return static::getInstance()->doCapture($until);
    }

    protected function doCapture(ErrorBagPoolInterface $until) : ErrorBagPoolInterface
    {
        $result = $this->stack->flush($until);

        return $result;
    }


    /**
     * > Завершает стек пулов, и возвращает новый пул
     */
    public static function flush() : ErrorBagPoolInterface
    {
        return static::getInstance()->doFlush();
    }

    protected function doFlush() : ErrorBagPoolInterface
    {
        $result = $this->stack->flush();

        return $result;
    }


    /**
     * > Возвращает текущий ErrorBag или создает null-object
     */
    public static function current() : ErrorBagPoolInterface
    {
        return static::getInstance()->doCurrent();
    }

    protected function doCurrent() : ErrorBagPoolInterface
    {
        $current = $this->stack->current();

        return $current ?? $this->factory->newErrorBagPool();
    }


    public static function getInstance(ErrorBagFactoryInterface $factory = null) : self
    {
        return static::$instances[ static::class ] = static::$instances[ static::class ]
            ?? ($factory ?? new ErrorBagFactory())->newErrorBag();
    }

    public static function setInstance(?self $instance) : self
    {
        if (! ($instance instanceof static)) {
            throw new LogicException(
                'The `instance` should be: ' . static::class
                . ' / ' . Lib::php_dump($instance)
            );
        }

        static::$instances[ static::class ] = $instance;

        return $instance;
    }

    /**
     * @var ErrorBagStackInterface[]
     */
    protected static $instances = [];
}
