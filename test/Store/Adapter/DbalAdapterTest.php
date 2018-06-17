<?php

namespace SimplePhpDocumentStore\Store\Adapter;

use Doctrine\DBAL\DriverManager;
use SimplePhpDocumentStore\Query\Query;
use SimplePhpDocumentStore\Query\Statement\OrStatement;
use SimplePhpDocumentStore\Query\Statement\WhereStatement;
use PHPUnit\Framework\TestCase;

class DbalAdapterTest extends TestCase
{
    /** @var DbalAdapter */
    private $dbalAdapter;

    public function setUp()
    {
        parent::setUp();

        $connectionParams = [
            'url' => 'sqlite:///:memory:',
        ];
        $conn = DriverManager::getConnection($connectionParams);
        $conn->exec('PRAGMA foreign_keys = ON');

        $dbalAdapter = new DbalAdapter($conn);
        $dbalAdapter->initDataStructure();
        $this->dbalAdapter = $dbalAdapter;
    }

    public function testSave()
    {
        $document1 = [
            'person' => [
                'head' => [
                    'nose' => 'big',
                    'eyes' => 'blue',
                ],
            ],
        ];
        $document2 = [
            'person' => [
                'head' => [
                    'nose' => 'big',
                    'eyes' => 'red',
                ],
            ],
        ];

        $this->dbalAdapter->store($document1);
        $this->dbalAdapter->store($document2);

        $query = new Query();
        $query->whereAnd(
            (new OrStatement())->add(
                new WhereStatement('person.head.eyes', '=', 'red'),
                new WhereStatement('person.head.eyes', '=', 'orange')
            )
        );

        $documents = $this->dbalAdapter->searchByQuery($query);
        $this->assertCount(1, $documents);
    }

    public function testUpdate()
    {
        $document1 = [
            'test' => 0,
        ];
        $id = $this->dbalAdapter->store($document1);

        $searchResult1 = $this->dbalAdapter->searchByQuery((new Query())->whereAnd(new WhereStatement('test', '=', '0')));

        $this->assertSame([$document1], array_values($searchResult1));

        $document2 = [
            'test' => 1,
        ];
        $this->dbalAdapter->update($id, $document2);
        $searchResult1 = $this->dbalAdapter->searchByQuery((new Query())->whereAnd(new WhereStatement('test', '=', '0')));
        $searchResult2 = $this->dbalAdapter->searchByQuery((new Query())->whereAnd(new WhereStatement('test', '=', '1')));

        $this->assertCount(0, $searchResult1);
        $this->assertCount(1, $searchResult2);
        $this->assertSame([$document2], array_values($searchResult2));
    }

    public function testSearchById()
    {
        $result1 = $this->dbalAdapter->searchById('random');
        $this->assertNull($result1);

        $document = [
            'test' => 'hello'
        ];
        $id = $this->dbalAdapter->store($document);

        $result2 = $this->dbalAdapter->searchById($id);
        $this->assertSame($document, $result2);
    }
}
