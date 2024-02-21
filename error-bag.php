<?php

class ErrorBagItem
{
    /**
     * @var mixed
     */
    public $body;
    /**
     * @var array
     */
    public $path;
    /**
     * @var array
     */
    public $tags;
}

class ErrorBag implements \Countable, \IteratorAggregate
{
    /**
     * @var ErrorBagItem[]
     */
    protected $errors;
    /**
     * @var ErrorBagItem[]
     */
    protected $warnings;


    public function count()
    {
        return count($this->errors ?? []) + count($this->warnings ?? []);
    }

    public function getIterator()
    {
        return new ArrayIterator(array_merge($this->errors ?? [], $this->warnings ?? []));
    }


    public function isEmpty() : bool
    {
        return ! empty($this->errors) || ! empty($this->warnings);
    }


    public function hasErrors() : bool
    {
        return ! empty($this->errors);
    }

    public function hasWarnings() : bool
    {
        return ! empty($this->warnings);
    }


    public function getErrors() : self
    {
        $instance = new static();

        foreach ( $this->errors ?? [] as $error ) {
            $_path = $error->path;
            $_tags = $error->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = new ErrorBagItem();
            $item->body = $error->body;
            $item->path = $_path;
            $item->tags = $_tags;

            $instance->errors[] = $item;
        }

        return $instance;
    }

    public function getWarnings() : self
    {
        $instance = new static();

        foreach ( $this->warnings ?? [] as $warning ) {
            $_path = $warning->path;
            $_tags = $warning->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = new ErrorBagItem();
            $item->body = $warning->body;
            $item->path = $_path;
            $item->tags = $_tags;

            $instance->warnings[] = $item;
        }

        return $instance;
    }


    public function getByPath($and, ...$orAnd) : self
    {
        array_unshift($orAnd, $and);

        $_orAnd = [];
        foreach ( $orAnd as $i => $and ) {
            $_and = [];
            if ($and instanceof stdClass) {
                foreach ( get_object_vars($and) as $v ) {
                    $_and[] = (array) $v;
                }

            } else {
                $_and[] = (array) $and;
            }

            foreach ( $_and as $ii => $path ) {
                $_orAnd[ $i ][ $ii ] = "\0" . implode("\0", (array) $path) . "\0";
            }
        }

        $instance = new static();

        foreach ( $this->errors ?? [] as $error ) {
            $pathString = "\0" . implode("\0", $error->path ?? []) . "\0";

            $found = true;

            foreach ( $_orAnd as $_and ) {
                $found = true;

                foreach ( $_and as $andPathString ) {
                    if (false === strpos($pathString, $andPathString)) {
                        $found = false;

                        break;
                    }
                }

                if ($found) {
                    break;
                }
            }

            if ($found) {
                $instance->errors[] = $error;
            }
        }

        foreach ( $this->warnings ?? [] as $warning ) {
            $pathString = "\0" . implode("\0", $warning->path ?? []) . "\0";

            $found = true;

            foreach ( $_orAnd as $_and ) {
                $found = true;

                foreach ( $_and as $andPathString ) {
                    if (false === strpos($pathString, $andPathString)) {
                        $found = false;

                        break;
                    }
                }

                if ($found) {
                    break;
                }
            }

            if ($found) {
                $instance->warnings[] = $warning;
            }
        }

        return $instance;
    }

    public function getByTags($and, ...$orAnd) : self
    {
        array_unshift($orAnd, $and);

        $_orAnd = [];
        foreach ( $orAnd as $i => $and ) {
            $_and = [];
            if ($and instanceof stdClass) {
                foreach ( get_object_vars($and) as $v ) {
                    $_and[] = (array) $v;
                }

            } else {
                $_and[] = (array) $and;
            }

            foreach ( $_and as $ii => $tags ) {
                $_orAnd[ $i ][ $ii ] = $tags;
            }
        }

        $instance = new static();

        foreach ( $this->errors ?? [] as $error ) {
            $tags = $error->tags ?? [];

            $found = true;

            foreach ( $_orAnd as $_and ) {
                $found = true;

                foreach ( $_and as $andTags ) {
                    if (! $andTags) {
                        continue;
                    }

                    if (array_diff($andTags, $tags)) {
                        $found = false;

                        break;
                    }
                }

                if ($found) {
                    break;
                }
            }

            if ($found) {
                $instance->errors[] = $error;
            }
        }

        foreach ( $this->warnings ?? [] as $warning ) {
            $tags = $warning->tags ?? [];

            $found = true;

            foreach ( $_orAnd as $_and ) {
                $found = true;

                foreach ( $_and as $andTags ) {
                    if (! $andTags) {
                        continue;
                    }

                    if (array_diff($andTags, $tags)) {
                        $found = false;

                        break;
                    }
                }

                if ($found) {
                    break;
                }
            }

            if ($found) {
                $instance->warnings[] = $warning;
            }
        }

        return $instance;
    }


    public function errors(array $errors, $path = null, $tags = null) : void
    {
        foreach ( $errors as $idx => $error ) {
            $this->error(
                $error,
                isset($path) ? array_merge((array) $path, [ $idx ]) : null,
                $tags
            );
        }
    }

    public function error($error, $path = null, $tags = null) : void
    {
        if (is_a($error, static::class)) {
            $this->mergeAsErrors($error, $path, $tags);

        } else {
            $_path = null;
            $_tags = null;

            if (isset($path)) $_path = array_map('strval', (array) $path);
            if (isset($tags)) $_tags = array_map('strval', (array) $tags);

            $item = new ErrorBagItem();
            $item->body = $error;
            $item->path = $_path;
            $item->tags = $_tags;

            $this->errors[] = $item;
        }
    }


    public function warnings(array $warnings, $path = null, $tags = null) : void
    {
        foreach ( $warnings as $idx => $warning ) {
            $this->warning(
                $warning,
                isset($path) ? array_merge((array) $path, [ $idx ]) : null,
                $tags
            );
        }
    }

    public function warning($warning, $path = null, $tags = null) : void
    {
        if (is_a($warning, static::class)) {
            $this->mergeAsWarnings($warning, $path, $tags);

        } else {
            $_path = null;
            $_tags = null;

            if (isset($path)) $_path = array_map('strval', (array) $path);
            if (isset($tags)) $_tags = array_map('strval', (array) $tags);

            $item = new ErrorBagItem();
            $item->body = $warning;
            $item->path = $_path;
            $item->tags = $_tags;

            $this->warnings[] = $item;
        }
    }


    public function merge(self $errorBag, $path = null, $tags = null) : self
    {
        foreach ( $errorBag->errors ?? [] as $error ) {
            $_path = $error->path;
            $_tags = $error->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = new ErrorBagItem();
            $item->body = $error->body;
            $item->path = $_path;
            $item->tags = $_tags;

            $this->errors[] = $item;
        }

        foreach ( $errorBag->warnings ?? [] as $warning ) {
            $_path = $warning->path;
            $_tags = $warning->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = new ErrorBagItem();
            $item->body = $warning->body;
            $item->path = $_path;
            $item->tags = $_tags;

            $this->warnings[] = $item;
        }

        return $this;
    }

    public function mergeAsErrors(self $errorBag, $path = null, $tags = null) : self
    {
        foreach ( $errorBag->errors ?? [] as $error ) {
            $_path = $error->path;
            $_tags = $error->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = new ErrorBagItem();
            $item->body = $error->body;
            $item->path = $_path;
            $item->tags = $_tags;

            $this->errors[] = $item;
        }

        foreach ( $errorBag->warnings ?? [] as $warning ) {
            $_path = $warning->path;
            $_tags = $warning->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = new ErrorBagItem();
            $item->body = $warning->body;
            $item->path = $_path;
            $item->tags = $_tags;

            $this->errors[] = $item;
        }

        return $this;
    }

    public function mergeAsWarnings(self $errorBag, $path = null, $tags = null) : self
    {
        foreach ( $errorBag->errors ?? [] as $error ) {
            $_path = $error->path;
            $_tags = $error->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = new ErrorBagItem();
            $item->body = $error->body;
            $item->path = $_path;
            $item->tags = $_tags;

            $this->warnings[] = $item;
        }

        foreach ( $errorBag->warnings ?? [] as $warning ) {
            $_path = $warning->path;
            $_tags = $warning->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = new ErrorBagItem();
            $item->body = $warning->body;
            $item->path = $_path;
            $item->tags = $_tags;

            $this->warnings[] = $item;
        }

        return $this;
    }


    public function toArray(string $implodeKeySeparator = null) : array
    {
        $implodeKeySeparator = $implodeKeySeparator ?? '.';

        $result = [];

        $result[ 'errors' ] = $this->convertToArray($this->errors ?? [], $implodeKeySeparator);
        $result[ 'warnings' ] = $this->convertToArray($this->warnings ?? [], $implodeKeySeparator);

        return $result;
    }

    public function toArrayNested(bool $asObject = null) : array
    {
        $asObject = $asObject ?? false;

        $result = [];

        $result[ 'errors' ] = $this->convertToArrayNested($this->errors ?? [], $asObject);
        $result[ 'warnings' ] = $this->convertToArrayNested($this->warnings ?? [], $asObject);

        return $result;
    }


    protected function convertToArray(array $items, string $implodeKeySeparator = null) : array
    {
        $result = [];

        if (! isset($implodeKeySeparator)) {
            return $items;
        }

        foreach ( $items as $i => $item ) {
            if (! ($item instanceof ErrorBagItem)) {
                throw new \LogicException('Each of `items` should be instance of: ' . ErrorBagItem::class);
            }

            $key = implode($implodeKeySeparator, $item->path);

            $result[ $key ][] = $item->body;
        }

        return $result;
    }

    protected function convertToArrayNested(array $items, bool $asObject = null) : array
    {
        $asObject = $asObject ?? true;

        $result = [];

        foreach ( $items as $item ) {
            if (! ($item instanceof ErrorBagItem)) {
                throw new \LogicException('Each of `items` should be instance of: ' . ErrorBagItem::class);
            }

            $row = $asObject
                ? $item
                : $item->body;

            $this->arraySet($result, $item->path, $row);
        }

        return $result;
    }


    protected function arraySet(array &$dst, array $path, $value) // : &mixed
    {
        $_path = $path;

        $ref =& $dst;

        while ( null !== key($_path) ) {
            $p = array_shift($_path);

            if (! array_key_exists($p, $ref)) {
                $ref[ $p ] = $_path
                    ? []
                    : null;
            }

            $ref =& $ref[ $p ];

            if ((! is_array($ref)) && $_path) {
                unset($ref);
                $ref = null;

                throw new \RuntimeException(
                    "Trying to traverse scalar value: "
                    . "{$p} / " . var_export($path, 1)
                );
            }
        }

        $ref = $value;

        return $ref;
    }
}

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


/**
 * > возвращает актуальный error-bag
 * > или создает новый и делает его актуальным
 */
function _error_bag(\ErrorBag &$current = null) : \ErrorBag
{
    $current = null;

    $stack = ErrorBagStack::getInstance();

    if (! $current = $stack->hasErrorBag()) {
        $_current = new \ErrorBag();

        $stack->pushErrorBag($_current);

        $current = $_current;
    }

    return $current;
}


/**
 * > создает и возвращает новый error-bag, делает его актуальным
 */
function _error_bag_push(\ErrorBag &$new = null) : \ErrorBag
{
    $new = null;

    $stack = ErrorBagStack::getInstance();

    $current = $stack->pushErrorBag();

    $new = $current;

    return $new;
}

/**
 * > забирает актуальный error-bag, если он был, делает его родителя актуальным
 * > если указан $verify, то когда последний не равен переданному, выбросит исключение
 */
function _error_bag_pop(?\ErrorBag $verify) : \ErrorBag
{
    $stack = ErrorBagStack::getInstance();

    $last = $stack->popErrorBag($verify);

    $last = $last ?? new \ErrorBag();

    return $last;
}

/**
 * > завершает все error-bag
 * > если указан $until, то завершает до указанного error-bag
 * > возвращает объединение всех error-bag, которые были завершены в виде нового error-bag
 */
function _error_bag_end(?\ErrorBag $until) : \ErrorBag
{
    $stack = ErrorBagStack::getInstance();

    $flush = $stack->endErrorBag($until);

    return $flush;
}


function _error_bag_warning($warning, $path = null, $tags = null) : void
{
    _error_bag($e);

    $e->warning($warning, $path, $tags);
}

function _error_bag_error($error, $path = null, $tags = null) : void
{
    _error_bag($e);

    $e->error($error, $path, $tags);
}

function _error_bag_merge($errorBag, $path = null, $tags = null) : void
{
    _error_bag($e);

    $e->merge($errorBag, $path, $tags);
}