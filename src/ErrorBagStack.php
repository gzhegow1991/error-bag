<?php

namespace Gzhegow\ErrorBag;

class ErrorBagStack
{
    protected $errorBagStack = [];
    protected $errorBag;


    public function hasErrorBag() : ?ErrorBag
    {
        return $this->errorBag;
    }

    public function getErrorBag() : ErrorBag
    {
        return $this->errorBag;
    }


    public function pushErrorBag(ErrorBag $current = null) : ErrorBag
    {
        $current = $current ?? new ErrorBag();

        $this->errorBagStack[] = $current;

        $this->errorBag = $current;

        return $current;
    }

    public function popErrorBag(?ErrorBag $verify) : ?ErrorBag
    {
        $errorBagLast = $this->errorBagStack
            ? end($this->errorBagStack)
            : null;

        if ($verify) {
            if (! $errorBagLast) {
                throw new \RuntimeException(
                    'ErrorBag stack is empty at the moment'
                );

            } elseif ($errorBagLast !== $verify) {
                throw new \RuntimeException(
                    'You possible forget somewhere to pop() previously started ErrorBag'
                );
            }
        }

        if ($errorBagLast) {
            array_pop($this->errorBagStack);

            $this->errorBag = $this->errorBagStack
                ? end($this->errorBagStack)
                : null;
        }

        return $errorBagLast;
    }


    public function startErrorBag(ErrorBag $new = null) : ErrorBag
    {
        $new = $new ?? new ErrorBag();

        $this->errorBagStack[] = $new;

        $this->errorBag = $new;

        return $new;
    }

    public function endErrorBag(?ErrorBag $until) : ErrorBag
    {
        $count = count($this->errorBagStack);

        $flush = new ErrorBag();

        for ( $i = $count - 1; $i >= 0; $i-- ) {
            $current = $this->errorBagStack[ $i ];

            unset($this->errorBagStack[ $i ]);

            $flush->merge($current);

            if ($until === $current) {
                break;
            }
        }

        $this->errorBag = $this->errorBagStack
            ? end($this->errorBagStack)
            : null;

        return $flush;
    }


    public static function getInstance() : self
    {
        return static::$instances[ static::class ] = static::$instances[ static::class ]
            ?? new static();
    }

    protected static $instances = [];
}
