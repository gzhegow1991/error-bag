<?php

namespace Gzhegow\ErrorBag;

use Gzhegow\ErrorBag\Struct\ErrorBagItem;


interface ErrorBagFacadeInterface
{
    public function newErrorBagItem() : ErrorBagItem;


    /**
     * > Позволяет сбросить стек пулов на другой или на пустой, возвращает текущий
     */
    public function reset(ErrorBagStackInterface $previous = null) : ErrorBagStackInterface;


    /**
     * > Создает дочерний / новый пул
     */
    public function begin(?ErrorBagPoolInterface &$new_) : ?ErrorBagPoolInterface;

    /**
     * > Возвращает текущий / создает новый пул
     */
    public function get(ErrorBagPoolInterface &$current_ = null) : ErrorBagPoolInterface;

    /**
     * > Завершает пул, если передать $verify - проверит, что пул был крайним в стеке
     * > Если не указать $until, завершит один последний пул
     */
    public function end(?ErrorBagPoolInterface $verify) : ErrorBagPoolInterface;

    /**
     * > Завершает стек до указанного пула, и возвращает новый пул
     * > Если не указать $until, завершит один последний пул
     */
    public function capture(ErrorBagPoolInterface $until) : ErrorBagPoolInterface;

    /**
     * > Завершает стек пулов, и возвращает новый пул
     */
    public function flush() : ErrorBagPoolInterface;

    /**
     * > Возвращает текущий ErrorBag или создает null-object
     */
    public function current() : ErrorBagPoolInterface;
}
