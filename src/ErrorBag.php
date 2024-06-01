<?php

namespace Gzhegow\ErrorBag;

class ErrorBag implements ErrorBagInterface
{
    const TYPE_ERR = 'ERR';
    const TYPE_MSG = 'MSG';

    const LIST_TYPE = [
        self::TYPE_ERR => true,
        self::TYPE_MSG => true,
    ];


    /**
     * @var ErrorBagItem[]
     */
    protected $errors;
    /**
     * @var ErrorBagItem[]
     */
    protected $messages;


    public function count() : int
    {
        return 0
            + count($this->errors ?? [])
            + count($this->messages ?? []);
    }

    public function getIterator() : \Traversable
    {
        return new \ArrayIterator(
            array_merge(
                $this->errors ?? [],
                $this->messages ?? []
            )
        );
    }


    public function isEmpty() : bool
    {
        return empty($this->errors) && empty($this->messages);
    }


    public function getItems() : array
    {
        $items = [
            static::TYPE_ERR => $this->errors ?? [],
            static::TYPE_MSG => $this->messages ?? [],
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

    public function getMessages() : self
    {
        $instance = new static();

        foreach ( $this->messages ?? [] as $message ) {
            $_path = $message->path;
            $_tags = $message->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = new ErrorBagItem();
            $item->body = $message->body;
            $item->path = $_path;
            $item->tags = $_tags;

            $instance->messages[] = $item;
        }

        return $instance;
    }


    public function getByPath($and, ...$orAnd) : self
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
                $instance->errors[] = $error;
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
                $instance->messages[] = $message;
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
                $instance->errors[] = $error;
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
                $instance->messages[] = $message;
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


    public function messages(array $messages, $path = null, $tags = null) : void
    {
        foreach ( $messages as $idx => $message ) {
            $this->message(
                $message,
                isset($path) ? array_merge((array) $path, [ $idx ]) : null,
                $tags
            );
        }
    }

    public function message($message, $path = null, $tags = null) : void
    {
        if (is_a($message, static::class)) {
            $this->mergeAsMessages($message, $path, $tags);

        } else {
            $_path = null;
            $_tags = null;

            if (isset($path)) $_path = array_map('strval', (array) $path);
            if (isset($tags)) $_tags = array_map('strval', (array) $tags);

            $item = new ErrorBagItem();
            $item->body = $message;
            $item->path = $_path;
            $item->tags = $_tags;

            $this->messages[] = $item;
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

        foreach ( $errorBag->messages ?? [] as $message ) {
            $_path = $message->path;
            $_tags = $message->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = new ErrorBagItem();
            $item->body = $message->body;
            $item->path = $_path;
            $item->tags = $_tags;

            $this->messages[] = $item;
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

        foreach ( $errorBag->messages ?? [] as $message ) {
            $_path = $message->path;
            $_tags = $message->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = new ErrorBagItem();
            $item->body = $message->body;
            $item->path = $_path;
            $item->tags = $_tags;

            $this->errors[] = $item;
        }

        return $this;
    }

    public function mergeAsMessages(self $errorBag, $path = null, $tags = null) : self
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

            $this->messages[] = $item;
        }

        foreach ( $errorBag->messages ?? [] as $message ) {
            $_path = $message->path;
            $_tags = $message->tags;

            if (isset($path)) $_path = array_merge((array) $path, $_path ?? []);
            if (isset($tags)) $_tags = array_unique(array_merge($_tags ?? [], (array) $tags));

            $item = new ErrorBagItem();
            $item->body = $message->body;
            $item->path = $_path;
            $item->tags = $_tags;

            $this->messages[] = $item;
        }

        return $this;
    }


    public function toArray(string $implodeKeySeparator = null) : array
    {
        $implodeKeySeparator = $implodeKeySeparator ?? '.';

        $result = [];

        $result[ static::TYPE_ERR ] = $this->convertToArray($this->errors ?? [], $implodeKeySeparator);
        $result[ static::TYPE_MSG ] = $this->convertToArray($this->messages ?? [], $implodeKeySeparator);

        return $result;
    }

    public function toArrayNested(bool $asObject = null) : array
    {
        $asObject = $asObject ?? false;

        $result = [];

        $result[ static::TYPE_ERR ] = $this->convertToArrayNested($this->errors ?? [], $asObject);
        $result[ static::TYPE_MSG ] = $this->convertToArrayNested($this->messages ?? [], $asObject);

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

            $key = implode($implodeKeySeparator, $item->path ?? []);

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

            ($item->path)
                ? Lib::array_set_path($result, $item->path, $row)
                : $result[] = $row;
        }

        return $result;
    }
}
