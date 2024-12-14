<?php

namespace Gzhegow\ErrorBag;


use Gzhegow\ErrorBag\Struct\ErrorBagItem;


class ErrorBagFacade implements ErrorBagFacadeInterface
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
        //
        ErrorBagStackInterface $stack
    )
    {
        $this->factory = $factory;

        $this->stack = $stack;
    }


    public function newErrorBagItem() : ErrorBagItem
    {
        return $this->factory->newErrorBagItem();
    }


    /**
     * > Позволяет сбросить стек пулов на другой или на пустой, возвращает текущий
     */
    public function reset(ErrorBagStackInterface $previous = null) : ErrorBagStackInterface
    {
        $latest = $this->stack;

        $this->stack = $previous ?? $this->factory->newErrorBagStacK();

        return $latest;
    }

    /**
     * > Создает дочерний / новый пул
     */
    public function begin(?ErrorBagPoolInterface &$new_) : ?ErrorBagPoolInterface
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
    public function get(ErrorBagPoolInterface &$current_ = null) : ErrorBagPoolInterface
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
    public function end(?ErrorBagPoolInterface $verify) : ErrorBagPoolInterface
    {
        $result = $this->stack->pop($verify);

        return $result;
    }

    /**
     * > Завершает стек до указанного пула, и возвращает новый пул
     * > Если не указать $until, завершит один последний пул
     */
    public function capture(ErrorBagPoolInterface $until) : ErrorBagPoolInterface
    {
        $result = $this->stack->flush($until);

        return $result;
    }

    /**
     * > Завершает стек пулов, и возвращает новый пул
     */
    public function flush() : ErrorBagPoolInterface
    {
        $result = $this->stack->flush();

        return $result;
    }

    /**
     * > Возвращает текущий ErrorBag или создает null-object
     */
    public function current() : ErrorBagPoolInterface
    {
        $current = $this->stack->current();

        return $current ?? $this->factory->newErrorBagPool();
    }
}
