<?php

namespace SimplePhpDocumentStore\Query\Statement;

class AndStatement
{
    /**
     * @var AndStatement[]|OrStatement[]|WhereStatement[]
     */
    private $terms;

    public function __construct()
    {
        $this->terms = [];
    }

    public function add(...$statements)
    {
        foreach ($statements as $statement) {
            $this->terms[] = $statement;
        }

        return $this;
    }

    public function getTerms()
    {
        return $this->terms;
    }
}