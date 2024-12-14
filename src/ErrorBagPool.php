<?php

namespace Gzhegow\ErrorBag;

use Gzhegow\Lib\Lib;
use Gzhegow\ErrorBag\Struct\ErrorBagItem;
use Gzhegow\ErrorBag\Exception\LogicException;


class ErrorBagPool implements ErrorBagPoolInterface
{
    /**
     * @var int
     */
    protected $id = 0;
    /**
     * @var ErrorBagItem[]
     */
    protected $errors;
    /**
     * @var ErrorBagItem[]
     */
    protected $messages;


    /**
     * @return int
     */
    public function count() : int
    {
        return 0
            + count($this->errors ?? [])
            + count($this->messages ?? []);
    }


    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new \ArrayIterator(
            []
            + ($this->errors ?? [])
            + ($this->messages ?? [])
        );
    }


    public function isEmpty() : bool
    {
        return empty($this->errors) && empty($this->messages);
    }


    public function hasItems() : bool
    {
        return ! (empty($this->errors) && empty($this->messages));
    }

    public function getItems() : array
    {
        $items = [
            ErrorBag::TYPE_ERR => $this->errors ?? [],
            ErrorBag::TYPE_MSG => $this->messages ?? [],
        ];

        return $items;
    }


    public function hasErrors() : bool
    {
        return ! empty($this->errors);
    }

    public function hasMessages() : bool
    {
        return ! empty($this->messages);
    }


    public function getErrors() : ErrorBagPoolInterface
    {
        $instance = new static();

        foreach ( $this->errors ?? [] as $error ) {
            $_path = $error->path;
            $_tags = $error->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = ErrorBag::newErrorBagItem();
            $item->payload = $error->payload;
            $item->path = $_path;
            $item->tags = $_tags;

            $id = ++$instance->id;

            $instance->errors[ $id ] = $item;
        }

        return $instance;
    }

    public function getMessages() : ErrorBagPoolInterface
    {
        $instance = new static();

        foreach ( $this->messages ?? [] as $message ) {
            $_path = $message->path;
            $_tags = $message->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = ErrorBag::newErrorBagItem();
            $item->payload = $message->payload;
            $item->path = $_path;
            $item->tags = $_tags;

            $id = ++$instance->id;

            $instance->messages[ $id ] = $item;
        }

        return $instance;
    }


    public function getByPath($and, ...$orAnd) : ErrorBagPoolInterface
    {
        array_unshift($orAnd, $and);

        $_orAnd = [];
        foreach ( $orAnd as $i => $and ) {
            $_and = [];
            if ($and instanceof \stdClass) {
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
                $id = ++$instance->id;

                $instance->errors[ $id ] = $error;
            }
        }

        foreach ( $this->messages ?? [] as $message ) {
            $pathString = "\0" . implode("\0", $message->path ?? []) . "\0";

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
                $id = ++$instance->id;

                $instance->messages[ $id ] = $message;
            }
        }

        return $instance;
    }

    public function getByTags($and, ...$orAnd) : ErrorBagPoolInterface
    {
        array_unshift($orAnd, $and);

        $_orAnd = [];
        foreach ( $orAnd as $i => $and ) {
            $_and = [];
            if ($and instanceof \stdClass) {
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
                $id = ++$instance->id;

                $instance->errors[ $id ] = $error;
            }
        }

        foreach ( $this->messages ?? [] as $message ) {
            $tags = $message->tags ?? [];

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
                $id = ++$instance->id;

                $instance->messages[ $id ] = $message;
            }
        }

        return $instance;
    }


    public function errors(array $errors, $path = null, $tags = null) : ErrorBagPoolInterface
    {
        foreach ( $errors as $idx => $error ) {
            $this->error(
                $error,
                isset($path) ? array_merge((array) $path, [ $idx ]) : null,
                $tags
            );
        }

        return $this;
    }

    public function error($error, $path = null, $tags = null) : ErrorBagPoolInterface
    {
        if (is_a($error, static::class)) {
            $this->mergeAsErrors($error, $path, $tags);

        } else {
            $_path = null;
            $_tags = null;

            if (isset($path)) $_path = array_map('strval', (array) $path);
            if (isset($tags)) $_tags = array_map('strval', (array) $tags);

            $item = ErrorBag::newErrorBagItem();
            $item->payload = $error;
            $item->path = $_path;
            $item->tags = $_tags;

            $id = ++$this->id;

            $this->errors[ $id ] = $item;
        }

        return $this;
    }


    public function messages(array $messages, $path = null, $tags = null) : ErrorBagPoolInterface
    {
        foreach ( $messages as $idx => $message ) {
            $this->message(
                $message,
                isset($path) ? array_merge((array) $path, [ $idx ]) : null,
                $tags
            );
        }

        return $this;
    }

    public function message($message, $path = null, $tags = null) : ErrorBagPoolInterface
    {
        if (is_a($message, static::class)) {
            $this->mergeAsMessages($message, $path, $tags);

        } else {
            $_path = null;
            $_tags = null;

            if (isset($path)) $_path = array_map('strval', (array) $path);
            if (isset($tags)) $_tags = array_map('strval', (array) $tags);

            $item = ErrorBag::newErrorBagItem();
            $item->payload = $message;
            $item->path = $_path;
            $item->tags = $_tags;

            $id = ++$this->id;

            $this->messages[ $id ] = $item;
        }

        return $this;
    }


    public function merge(ErrorBagPoolInterface $pool, $path = null, $tags = null) : ErrorBagPoolInterface
    {
        $this->mergeErrors($pool, $path, $tags);
        $this->mergeMessages($pool, $path, $tags);

        return $this;
    }

    public function mergeErrors(ErrorBagPoolInterface $pool, $path = null, $tags = null) : ErrorBagPoolInterface
    {
        foreach ( $pool->errors ?? [] as $error ) {
            $_path = $error->path;
            $_tags = $error->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = ErrorBag::newErrorBagItem();
            $item->payload = $error->payload;
            $item->path = $_path;
            $item->tags = $_tags;

            $id = ++$this->id;

            $this->errors[ $id ] = $item;
        }

        return $this;
    }

    public function mergeMessages(ErrorBagPoolInterface $pool, $path = null, $tags = null) : ErrorBagPoolInterface
    {
        foreach ( $pool->messages ?? [] as $message ) {
            $_path = $message->path;
            $_tags = $message->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = ErrorBag::newErrorBagItem();
            $item->payload = $message->payload;
            $item->path = $_path;
            $item->tags = $_tags;

            $id = ++$this->id;

            $this->messages[ $id ] = $item;
        }

        return $this;
    }

    public function mergeAsErrors(ErrorBagPoolInterface $pool, $path = null, $tags = null) : ErrorBagPoolInterface
    {
        foreach ( $pool->errors ?? [] as $error ) {
            $_path = $error->path;
            $_tags = $error->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = ErrorBag::newErrorBagItem();
            $item->payload = $error->payload;
            $item->path = $_path;
            $item->tags = $_tags;

            $id = ++$this->id;

            $this->errors[ $id ] = $item;
        }

        foreach ( $pool->messages ?? [] as $message ) {
            $_path = $message->path;
            $_tags = $message->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = ErrorBag::newErrorBagItem();
            $item->payload = $message->payload;
            $item->path = $_path;
            $item->tags = $_tags;

            $id = ++$this->id;

            $this->errors[ $id ] = $item;
        }

        return $this;
    }

    public function mergeAsMessages(ErrorBagPoolInterface $pool, $path = null, $tags = null) : ErrorBagPoolInterface
    {
        foreach ( $pool->errors ?? [] as $error ) {
            $_path = $error->path;
            $_tags = $error->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = ErrorBag::newErrorBagItem();
            $item->payload = $error->payload;
            $item->path = $_path;
            $item->tags = $_tags;

            $id = ++$this->id;

            $this->messages[ $id ] = $item;
        }

        foreach ( $pool->messages ?? [] as $message ) {
            $_path = $message->path;
            $_tags = $message->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = ErrorBag::newErrorBagItem();
            $item->payload = $message->payload;
            $item->path = $_path;
            $item->tags = $_tags;

            $id = ++$this->id;

            $this->messages[ $id ] = $item;
        }

        return $this;
    }


    public function toErrors() : ErrorBagPoolInterface
    {
        $instance = new static();

        foreach ( $this->errors ?? [] as $error ) {
            $_path = $error->path;
            $_tags = $error->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = ErrorBag::newErrorBagItem();
            $item->payload = $error->payload;
            $item->path = $_path;
            $item->tags = $_tags;

            $id = ++$instance->id;

            $instance->errors[ $id ] = $item;
        }

        foreach ( $this->messages ?? [] as $error ) {
            $_path = $error->path;
            $_tags = $error->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = ErrorBag::newErrorBagItem();
            $item->payload = $error->payload;
            $item->path = $_path;
            $item->tags = $_tags;

            $id = ++$instance->id;

            $instance->errors[ $id ] = $item;
        }

        return $instance;
    }

    public function toMessages() : ErrorBagPoolInterface
    {
        $instance = new static();

        foreach ( $this->errors ?? [] as $error ) {
            $_path = $error->path;
            $_tags = $error->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = ErrorBag::newErrorBagItem();
            $item->payload = $error->payload;
            $item->path = $_path;
            $item->tags = $_tags;

            $id = ++$instance->id;

            $instance->messages[ $id ] = $item;
        }

        foreach ( $this->messages ?? [] as $error ) {
            $_path = $error->path;
            $_tags = $error->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = ErrorBag::newErrorBagItem();
            $item->payload = $error->payload;
            $item->path = $_path;
            $item->tags = $_tags;

            $id = ++$instance->id;

            $instance->messages[ $id ] = $item;
        }

        return $instance;
    }


    public function toArray(string $implodeKeySeparator = null) : array
    {
        $implodeKeySeparator = $implodeKeySeparator ?? '.';

        $result = [];

        $result[ ErrorBag::TYPE_ERR ] = $this->convertToArray($this->errors ?? [], $implodeKeySeparator);
        $result[ ErrorBag::TYPE_MSG ] = $this->convertToArray($this->messages ?? [], $implodeKeySeparator);

        return $result;
    }

    public function toArrayNested(bool $asObject = null) : array
    {
        $asObject = $asObject ?? false;

        $result = [];

        $result[ ErrorBag::TYPE_ERR ] = $this->convertToArrayNested($this->errors ?? [], $asObject);
        $result[ ErrorBag::TYPE_MSG ] = $this->convertToArrayNested($this->messages ?? [], $asObject);

        return $result;
    }


    protected function convertToArray(array $items, string $implodeKeySeparator = null) : array
    {
        $result = [];

        if (! isset($implodeKeySeparator)) {
            return $items;
        }

        foreach ( $items as $item ) {
            if (! ($item instanceof ErrorBagItem)) {
                throw new LogicException(
                    'Each of `items` should be instance of: ' . ErrorBagItem::class
                );
            }

            if (! $item->path) {
                $key = '';

            } else {
                $path = [];
                foreach ( $item->path as $p ) {
                    $p = [ $p ];

                    $implode = [];
                    array_walk_recursive($p, static function ($value) use (&$implode) {
                        $implode[] = Lib::parse_string_not_empty($value);
                    });
                    $implode = implode('.', $implode);

                    $path[] = $implode;
                }

                $key = implode($implodeKeySeparator, $path);
            }

            $result[ $key ][] = $item->payload;
        }

        return $result;
    }

    protected function convertToArrayNested(array $items, bool $asObject = null) : array
    {
        $asObject = $asObject ?? true;

        $result = [];

        foreach ( $items as $item ) {
            if (! ($item instanceof ErrorBagItem)) {
                throw new LogicException(
                    'Each of `items` should be instance of: ' . ErrorBagItem::class
                );
            }

            $row = $asObject
                ? $item
                : $item->payload;

            if (! $item->path) {
                $result[] = $row;

            } else {
                $path = [];
                foreach ( $item->path as $p ) {
                    $p = [ $p ];

                    $implode = [];
                    array_walk_recursive($p, static function ($value) use (&$implode) {
                        $implode[] = Lib::parse_string_not_empty($value);
                    });
                    $implode = implode('.', $implode);

                    $path[] = $implode;
                }

                Lib::array_set_path($result, $path, $row);
            }
        }

        return $result;
    }
}
