<?php

namespace Gzhegow\ErrorBag;

use Gzhegow\ErrorBag\Exception\RuntimeException;


class ErrorBagStack implements ErrorBagStackInterface
{
    /**
     * @var ErrorBagFactoryInterface
     */
    protected $factory;

    /**
     * @var ErrorBagPoolInterface[]
     */
    protected $stack = [];
    /**
     * @var ErrorBagPoolInterface
     */
    protected $pool;


    public function __construct(ErrorBagFactoryInterface $factory)
    {
        $this->factory = $factory;
    }


    public function current() : ?ErrorBagPoolInterface
    {
        return $this->pool;
    }


    public function has(ErrorBagPoolInterface $pool) : ?ErrorBagPoolInterface
    {
        return $this->stack[ spl_object_id($pool) ] ?? null;
    }

    public function get(ErrorBagPoolInterface $pool) : ErrorBagPoolInterface
    {
        return $this->stack[ spl_object_id($pool) ];
    }


    public function push(ErrorBagPoolInterface $new) : ErrorBagStackInterface
    {
        $this->stack[ spl_object_id($new) ] = $new;

        $this->pool = $new;

        return $this;
    }

    public function pop(?ErrorBagPoolInterface $verify) : ErrorBagPoolInterface
    {
        $result = $this->factory->newErrorBagPool();

        if ($verify) {
            if (! $this->stack) {
                throw new RuntimeException(
                    'The pool stack is empty at the moment'
                );

            } else {
                $current = end($this->stack);

                if ($current !== $verify) {
                    throw new RuntimeException(
                        [
                            'Passed pool does not match latest pool',
                            $verify,
                            $current,
                        ]
                    );
                }

                array_pop($this->stack);

                $result->merge($current);
            }

        } elseif ($this->stack) {
            $current = array_pop($this->stack);

            $result->merge($current);
        }

        $this->pool = end($this->stack) ?: null;

        return $result;
    }

    public function flush(ErrorBagPoolInterface $until = null) : ErrorBagPoolInterface
    {
        $hasUntil = (null !== $until);

        $result = $this->factory->newErrorBagPool();

        if ($hasUntil) {
            if (! $this->has($until)) {
                throw new RuntimeException(
                    [
                        'Passed pool is missing in the stack',
                        $until,
                    ]
                );
            }
        }

        foreach ( array_reverse($this->stack, true) as $i => $current ) {
            if ($hasUntil && ($current === $until)) {
                break;
            }

            $result->merge($current);

            unset($this->stack[ $i ]);
        }

        $this->pool = end($this->stack) ?: null;

        return $result;
    }
}
