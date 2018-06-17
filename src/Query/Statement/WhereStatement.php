<?php

namespace SimplePhpDocumentStore\Query\Statement;

class WhereStatement
{
    /**
     * @var string
     */
    private $path;
    /**
     * @var string
     */
    private $operator;
    /**
     * @var mixed
     */
    private $value;

    /**
     * WhereCriteria constructor.
     *
     * @param string $path
     * @param string $operator
     * @param mixed  $value
     */
    public function __construct(string $path,
                                string $operator,
                                $value)
    {
        $this->path = $path;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @codeCoverageIgnore
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}