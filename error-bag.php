<?php

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


    public function pushErrorBag(ErrorBag $errorBag) : void
    {
        $this->errorBagStack[] = $errorBag;

        $this->errorBag = $errorBag;
    }

    public function popErrorBag(ErrorBag $errorBag = null) : ErrorBag
    {
        if (! $this->errorBagStack) {
            throw new \BadMethodCallException('The `errorBagStack` should be not-empty');
        }

        $errorBagLast = array_pop($this->errorBagStack);

        if ($errorBag && ($errorBagLast !== $errorBag)) {
            throw new \RuntimeException(
                'You possible forget somewhere to pop() previously started error bag, passed and last should be equal'
            );
        }

        $this->errorBag = $this->errorBagStack
            ? end($this->errorBagStack)
            : null;

        return $errorBagLast;
    }


    public function startErrorBag(ErrorBag $errorBag) : void
    {
        $this->errorBagStack[] = $errorBag;

        $this->errorBag = $errorBag;
    }

    /**
     * @param ErrorBag $errorBag
     *
     * @return ErrorBag[]
     */
    public function endErrorBag(ErrorBag $errorBag) : array
    {
        if (false === ($key = array_search($errorBag, $this->errorBagStack, true))) {
            throw new \RuntimeException('ErrorBag is not found in stack: '
                . ErrorBag::class . '#' . spl_object_id($errorBag)
            );
        }

        $stack = [];

        for ( $i = $key; $i < count($this->errorBagStack); $i++ ) {
            $stack[ $i ] = $this->errorBagStack[ $i ];

            unset($this->errorBagStack[ $i ]);
        }

        return $stack;
    }


    public static function getInstance() : self
    {
        return static::$instances[ static::class ] = static::$instances[ static::class ]
            ?? new static();
    }

    protected static $instances = [];
}

class ErrorBag
{
    protected const TYPE_WARNING = 0;
    protected const TYPE_ERROR   = 1;


    /**
     * @var array<int, string[]>
     */
    protected $pathes = [];
    /**
     * @var array<int, int>
     */
    protected $types = [];
    /**
     * @var array
     */
    protected $list = [];
    /**
     * @var array<int, string[]>
     */
    protected $tags = [];


    public function isEmpty() : bool
    {
        return ! empty($this->list);
    }

    public function isErrors() : bool
    {
        return ! empty($this->types)
            && in_array(static::TYPE_ERROR, $this->types);
    }

    public function isWarnings() : bool
    {
        return ! empty($this->types)
            && in_array(static::TYPE_WARNING, $this->types);
    }


    public function hasErrors() : ?array
    {
        $result = null;

        foreach ( $this->list as $idx => $error ) {
            if (static::TYPE_ERROR === $this->types[ $idx ]) {
                $result[ $idx ] = $error;
            }
        }

        return $result;
    }

    public function hasWarnings() : ?array
    {
        $result = null;

        foreach ( $this->list as $idx => $error ) {
            if (static::TYPE_WARNING === $this->types[ $idx ]) {
                $result[ $idx ] = $error;
            }
        }

        return $result;
    }


    public function getErrors() : array
    {
        return $this->hasErrors();
    }

    public function getWarnings() : array
    {
        return $this->hasWarnings();
    }


    protected function _getErrors() : array
    {
        $list = $this->hasErrors() ?? [];

        $types = [];
        $pathes = [];
        $tags = [];
        foreach ( $list as $idx => $error ) {
            $types[ $idx ] = $this->types[ $idx ];

            $pathes[ $idx ] = $this->pathes[ $idx ] ?? [];
            $tags[ $idx ] = $this->tags[ $idx ] ?? [];
        }

        return [
            $types,
            $list,
            $pathes,
            $tags,
        ];
    }

    protected function _getWarnings() : array
    {
        $list = $this->hasWarnings() ?? [];

        $types = [];
        $pathes = [];
        $tags = [];
        foreach ( $list as $idx => $error ) {
            $types[ $idx ] = $this->types[ $idx ];

            $pathes[ $idx ] = $this->pathes[ $idx ] ?? [];
            $tags[ $idx ] = $this->tags[ $idx ] ?? [];
        }

        return [
            $types,
            $list,
            $pathes,
            $tags,
        ];
    }


    public function getByPath(array $path, array ...$orPathes) : self
    {
        array_unshift($orPathes, $path);

        $instance = new static();

        foreach ( $this->list as $i => $item ) {
            $pathString = "\0" . implode("\0", $this->pathes[ $i ] ?? []) . "\0";

            foreach ( $orPathes as $orPath ) {
                if (! $orPath) continue;

                $orPathString = "\0" . implode("\0", $orPath) . "\0";

                if (false === strpos($pathString, $orPathString)) {
                    continue 2;
                }
            }

            end($instance->list);
            $ii = (null !== ($key = key($instance->list))) ? $key + 1 : 0;

            $instance->types[ $ii ] = $this->types[ $i ];
            $instance->list[ $ii ] = $this->list[ $i ];

            if (isset($this->pathes[ $i ])) $instance->pathes[ $ii ] = $this->pathes[ $i ];
            if (isset($this->tags[ $i ])) $instance->tags[ $ii ] = $this->tags[ $i ];
        }

        return $instance;
    }

    public function getByTags(array $tags, array ...$orTags) : self
    {
        array_unshift($orTags, $tags);

        $instance = new static();

        foreach ( $this->list as $i => $item ) {
            $itemTags = $this->tags[ $i ];

            foreach ( $orTags as $orTagsCurrent ) {
                if (! $orTagsCurrent) continue;

                if (array_diff($orTagsCurrent, $itemTags)) {
                    continue 2;
                }
            }

            end($instance->list);
            $ii = (null !== ($key = key($instance->list))) ? $key + 1 : 0;

            $instance->types[ $ii ] = $this->types[ $i ];
            $instance->list[ $ii ] = $this->list[ $i ];

            if (isset($this->pathes[ $i ])) $instance->pathes[ $ii ] = $this->pathes[ $i ];
            if (isset($this->tags[ $i ])) $instance->tags[ $ii ] = $this->tags[ $i ];
        }

        return $instance;
    }


    public function addErrors(array $errors, array $path = null, array $tags = null) : void
    {
        foreach ( $errors as $idx => $error ) {
            $this->addError(
                $error,
                $path ? array_merge($path, [ $idx ]) : null,
                $tags
            );
        }
    }

    public function addError($error, array $path = null, array $tags = null) : void
    {
        if (is_a($error, static::class)) {
            $this->mergeBagAsErrors($error, $path, $tags);

        } else {
            end($this->list);
            $idx = (null !== ($key = key($this->list))) ? $key + 1 : 0;

            $this->types[ $idx ] = static::TYPE_ERROR;
            $this->list[ $idx ] = $error;

            if ($path) $this->pathes[ $idx ] = array_map('strval', $path);
            if ($tags) $this->tags[ $idx ] = array_map('strval', $tags);
        }
    }


    public function addWarnings(array $warnings, array $path = null, array $tags = null) : void
    {
        foreach ( $warnings as $idx => $warning ) {
            $this->addWarning(
                $warning,
                $path ? array_merge($path, [ $idx ]) : null,
                $tags
            );
        }
    }

    public function addWarning($warning, array $path = null, array $tags = null) : void
    {
        if (is_a($warning, static::class)) {
            $this->mergeBagAsWarnings($warning, $path, $tags);

        } else {
            end($this->list);
            $idx = (null !== ($key = key($this->list))) ? $key + 1 : 0;

            $this->types[ $idx ] = static::TYPE_WARNING;
            $this->list[ $idx ] = $warning;

            if ($path) $this->pathes[ $idx ] = array_map('strval', $path);
            if ($tags) $this->tags[ $idx ] = array_map('strval', $tags);
        }
    }


    public function mergeBag(self $errorBag, array $path = null, array $tags = null) : void
    {
        [ $_types, $_list, $_pathes, $_tags ] = $errorBag->_getErrors();

        foreach ( array_keys($_types) as $idx ) {
            $typeNew = self::TYPE_ERROR;
            $itemNew = $_list[ $idx ];
            $pathNew = array_merge($path ?? [], $_pathes[ $idx ]);
            $tagsNew = array_merge($_tags[ $idx ], $tags ?? []);

            end($this->list);
            $idx = (null !== ($key = key($this->list))) ? $key + 1 : 0;

            $this->types[ $idx ] = $typeNew;
            $this->list[ $idx ] = $itemNew;
            $this->pathes[ $idx ] = $pathNew;
            $this->tags[ $idx ] = $tagsNew;
        }

        [ $_types, $_list, $_pathes, $_tags ] = $errorBag->_getWarnings();

        foreach ( array_keys($_types) as $idx ) {
            $typeNew = self::TYPE_WARNING;
            $itemNew = $_list[ $idx ];
            $pathNew = array_merge($path ?? [], $_pathes[ $idx ]);
            $tagsNew = array_merge($_tags[ $idx ], $tags ?? []);

            end($this->list);
            $idx = (null !== ($key = key($this->list))) ? $key + 1 : 0;

            $this->types[ $idx ] = $typeNew;
            $this->list[ $idx ] = $itemNew;
            $this->pathes[ $idx ] = $pathNew;
            $this->tags[ $idx ] = $tagsNew;
        }
    }

    public function mergeBagAsErrors(self $errorBag, array $path = null, array $tags = null) : void
    {
        [ $_types, $_list, $_pathes, $_tags ] = $errorBag->_getErrors();

        foreach ( array_keys($_types) as $idx ) {
            $typeNew = self::TYPE_ERROR;
            $itemNew = $_list[ $idx ];
            $pathNew = array_merge($path ?? [], $_pathes[ $idx ]);
            $tagsNew = array_merge($_tags[ $idx ], $tags ?? []);

            end($this->list);
            $idx = (null !== ($key = key($this->list))) ? $key + 1 : 0;

            $this->types[ $idx ] = $typeNew;
            $this->list[ $idx ] = $itemNew;
            $this->pathes[ $idx ] = $pathNew;
            $this->tags[ $idx ] = $tagsNew;
        }

        [ $_types, $_list, $_pathes, $_tags ] = $errorBag->_getWarnings();

        foreach ( array_keys($_types) as $idx ) {
            $typeNew = self::TYPE_ERROR;
            $itemNew = $_list[ $idx ];
            $pathNew = array_merge($path ?? [], $_pathes[ $idx ]);
            $tagsNew = array_merge($_tags[ $idx ], $tags ?? []);

            end($this->list);
            $idx = (null !== ($key = key($this->list))) ? $key + 1 : 0;

            $this->types[ $idx ] = $typeNew;
            $this->list[ $idx ] = $itemNew;
            $this->pathes[ $idx ] = $pathNew;
            $this->tags[ $idx ] = $tagsNew;
        }
    }

    public function mergeBagAsWarnings(self $errorBag, array $path = null, array $tags = null) : void
    {
        [ $_types, $_list, $_pathes, $_tags ] = $errorBag->_getErrors();

        foreach ( array_keys($_types) as $idx ) {
            $typeNew = self::TYPE_WARNING;
            $itemNew = $_list[ $idx ];
            $pathNew = array_merge($path ?? [], $_pathes[ $idx ]);
            $tagsNew = array_merge($_tags[ $idx ], $tags ?? []);

            end($this->list);
            $idx = (null !== ($key = key($this->list))) ? $key + 1 : 0;

            $this->types[ $idx ] = $typeNew;
            $this->list[ $idx ] = $itemNew;
            $this->pathes[ $idx ] = $pathNew;
            $this->tags[ $idx ] = $tagsNew;
        }

        [ $_types, $_list, $_pathes, $_tags ] = $errorBag->_getWarnings();

        foreach ( array_keys($_types) as $idx ) {
            $typeNew = self::TYPE_WARNING;
            $itemNew = $_list[ $idx ];
            $pathNew = array_merge($path ?? [], $_pathes[ $idx ]);
            $tagsNew = array_merge($_tags[ $idx ], $tags ?? []);

            end($this->list);
            $idx = (null !== ($key = key($this->list))) ? $key + 1 : 0;

            $this->types[ $idx ] = $typeNew;
            $this->list[ $idx ] = $itemNew;
            $this->pathes[ $idx ] = $pathNew;
            $this->tags[ $idx ] = $tagsNew;
        }
    }


    public function toArray(bool $asObject = null) : array
    {
        $asObject = $asObject ?? false;

        $result = [];

        $result[ 'errors' ] = $this->convertToArray($asObject, ...$this->_getErrors());
        $result[ 'warnings' ] = $this->convertToArray($asObject, ...$this->_getWarnings());

        return $result;
    }

    public function toArrayNested(bool $asObject = null) : array
    {
        $asObject = $asObject ?? false;

        $result = [];

        $result[ 'errors' ] = $this->convertToArrayNested($asObject, ...$this->_getErrors());
        $result[ 'warnings' ] = $this->convertToArrayNested($asObject, ...$this->_getWarnings());

        return $result;
    }


    protected function convertToArray(
        ?bool $asObject,
        array $types,
        array $list,
        array $pathes,
        array $tags
    ) : array
    {
        $asObject = $asObject ?? true;

        $result = [];

        foreach ( array_keys($types) as $i ) {
            $_item = $list[ $i ];

            if (! $asObject) {
                $row = $_item;

            } else {
                $_type = $types[ $i ];
                $_path = $pathes[ $i ];
                $_tags = $tags[ $i ];

                $row = (object) [
                    'path' => $_path,
                    'type' => ($_type === self::TYPE_WARNING) ? 'warning' : 'error',
                    'item' => $_item,
                    'tags' => $_tags,
                ];
            }

            $result[ $i ] = $row;
        }

        return $result;
    }

    protected function convertToArrayNested(
        ?bool $asObject,
        array $types,
        array $list,
        array $pathes,
        array $tags
    ) : array
    {
        $asObject = $asObject ?? true;

        $result = [];

        foreach ( array_keys($types) as $i ) {
            $_item = $list[ $i ];
            $_path = $pathes[ $i ];

            if (! $asObject) {
                $row = $_item;

            } else {
                $_type = $types[ $i ];
                $_tags = $tags[ $i ];

                $row = (object) [
                    'type' => ($_type === self::TYPE_WARNING) ? 'warning' : 'error',
                    'item' => $_item,
                    'tags' => $_tags,
                ];
            }

            $this->arraySet($result, $_path, $row);
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


function _error_bag(\ErrorBag &$ref = null) : \ErrorBag
{
    $ref = null;

    $scope = ErrorBagStack::getInstance();

    if (! $ref = $scope->hasErrorBag()) {
        $ref = new \ErrorBag();

        $scope->pushErrorBag($ref);
    }

    return $ref;
}


function _error_bag_push(\ErrorBag &$ref = null) : \ErrorBag
{
    $ref = null;

    $scope = ErrorBagStack::getInstance();

    $ref = new \ErrorBag();

    $scope->pushErrorBag($ref);

    return $ref;
}

function _error_bag_pop(\ErrorBag $errorBagToVerify = null) : ErrorBag
{
    $scope = ErrorBagStack::getInstance();

    $errorBagLast = $scope->popErrorBag($errorBagToVerify);

    return $errorBagLast;
}


function _error_bag_warning($warning, array $path = null, array $tags = null) : void
{
    $scope = ErrorBagStack::getInstance();

    if ($errorBagCurrent = $scope->hasErrorBag()) {
        $errorBagCurrent->addWarning($warning, $path, $tags);
    }
}

function _error_bag_error($error, array $path = null, array $tags = null) : void
{
    $scope = ErrorBagStack::getInstance();

    if ($errorBagCurrent = $scope->hasErrorBag()) {
        $errorBagCurrent->addError($error, $path, $tags);
    }
}

function _error_bag_merge(\ErrorBag $errorBag, array $path = null, array $tags = null) : void
{
    $scope = ErrorBagStack::getInstance();

    if ($errorBagCurrent = $scope->hasErrorBag()) {
        $errorBagCurrent->mergeBag($errorBag, $path, $tags);
    }
}
