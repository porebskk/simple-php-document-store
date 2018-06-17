<?php

namespace SimplePhpDocumentStore\Query;

use SimplePhpDocumentStore\Query\Statement\AndStatement;

class Query
{
    /** @var AndStatement */
    private $rootAndStatement;

    public function __construct()
    {
        $this->rootAndStatement = new AndStatement();
    }

    public function whereAnd(...$statements)
    {
        foreach ($statements as $statement) {
            $this->rootAndStatement->add($statement);
        }

        return $this;
    }

    /**
     * @return AndStatement
     */
    public function getRootAnd()
    {
        return $this->rootAndStatement;
    }
}