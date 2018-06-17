<?php

namespace SimplePhpDocumentStore\Store\Adapter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Table;
use Exception;
use Ramsey\Uuid\Uuid;
use SimplePhpDocumentStore\Query\Query;
use SimplePhpDocumentStore\Query\Statement\AndStatement;
use SimplePhpDocumentStore\Query\Statement\OrStatement;
use SimplePhpDocumentStore\Query\Statement\WhereStatement;
use SimplePhpDocumentStore\Store\ArrayPathGenerator;
use SimplePhpDocumentStore\Store\StoreInterface;

class DbalAdapter implements StoreInterface
{
    public const TABLE_NAME_DATA = 'simple_php_document_store_data';
    public const TABLE_NAME_DOCUMENT_PATH = 'simple_php_document_store_path';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * DbalAdapter constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function initDataStructure(): void
    {
        $schemaManager = $this->connection->getSchemaManager();

        //data table
        if ($schemaManager->tablesExist([self::TABLE_NAME_DATA]) === false) {
            $table = new Table(self::TABLE_NAME_DATA);
            $table->addColumn('id', 'string', ['length' => 32]);
            $table->addColumn('data', 'text');
            $table->setPrimaryKey(['id']);
            $schemaManager->createTable($table);
        }

        //document path table
        if ($schemaManager->tablesExist([self::TABLE_NAME_DOCUMENT_PATH]) === false) {
            $table = new Table(self::TABLE_NAME_DOCUMENT_PATH);
            $table->addColumn('id', 'string', ['length' => 32]);
            $table->addColumn('data_id', 'string', ['length' => 32]);
            $table->addColumn('path', 'text');
            $table->addColumn('value_raw', 'text');
            $table->setPrimaryKey(['id']);
            $table->addIndex([
                'data_id',
                'path',
                'value_raw',
            ], 'idx_path_valueRaw');
            $table->addForeignKeyConstraint(self::TABLE_NAME_DATA, ['data_id'], ['id'], ['onDelete' => 'CASCADE']);
            $schemaManager->createTable($table);
        }
    }

    /**
     * @param array $document
     *
     * @return mixed Unique Database-ID for the document.
     * @throws Exception
     */
    public function store(array $document)
    {
        $uuidDocument = Uuid::uuid4();
        $this->connection->beginTransaction();
        try {
            $this->saveDocumentAndPaths($document, $uuidDocument);
            $this->connection->commit();
        } catch (Exception $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        return $uuidDocument;
    }

    public function searchByQuery(Query $query): array
    {
        $andStatement = $query->getRootAnd();

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->from(self::TABLE_NAME_DATA, 'dataTable');
        $queryBuilder->select('dataTable.id', 'dataTable.data');
        $queryBuilder->innerJoin('dataTable', self::TABLE_NAME_DOCUMENT_PATH, 'pathTable', 'dataTable.id = pathTable.data_id');

        $rootAndStatement = $this->transformStoreStatementToQueryBuilderStatements($queryBuilder, $andStatement, $queryBuilder->expr());
        if ($rootAndStatement) {
            $queryBuilder->where($rootAndStatement);
        }

        $queryBuilder->groupBy('dataTable.id');

        if (is_numeric($query->getMaxDocumentCount())) {
            $queryBuilder->setMaxResults($query->getMaxDocumentCount());
        }

        $result = $queryBuilder->execute()
                               ->fetchAll();
        $columinizedResult = array_column($result, 'data', 'id');

        return array_map('unserialize', $columinizedResult);
    }

    private function transformStoreStatementToQueryBuilderStatements(QueryBuilder $queryBuilder,
                                                                     $statement,
                                                                     ExpressionBuilder $exprBuilder)
    {
        if ($statement instanceof AndStatement) {
            $terms = $statement->getTerms();
            if (count($terms) === 0) {
                return null;
            }
            $andX = $exprBuilder->andX();
            foreach ($terms as $term) {
                $expression = $this->transformStoreStatementToQueryBuilderStatements($queryBuilder, $term, $exprBuilder);
                if ($expression) {
                    $andX->add($expression);
                }
            }
            if ($andX->count() > 0) {
                return $andX;
            }

            return null;
        }

        if ($statement instanceof OrStatement) {
            $terms = $statement->getTerms();
            if (count($terms) === 0) {
                return null;
            }
            $orX = $exprBuilder->orX();
            foreach ($terms as $term) {
                $expression = $this->transformStoreStatementToQueryBuilderStatements($queryBuilder, $term, $exprBuilder);
                if ($expression) {
                    $orX->add($expression);
                }
            }
            if ($orX->count() > 0) {
                return $orX;
            }

            return null;
        }

        if ($statement instanceof WhereStatement) {
            $param = str_replace('.', '', uniqid('p', true));
            $comparison = $exprBuilder->andX(
                $exprBuilder->eq('pathTable.path', '"' . $statement->getPath() . '"'),
                $exprBuilder->comparison('pathTable.value_raw', $statement->getOperator(), ':' . $param)
            );
            $queryBuilder->setParameter($param, $statement->getValue());

            return $comparison;
        }
    }

    /**
     * Updates a document.
     *
     * @param string $id Unique Database-ID for the document.
     * @param array  $document
     *
     * @return bool
     */
    public function update(string $id,
                           array $document): bool
    {
        $this->connection->beginTransaction();
        try {
            $this->connection->delete(self::TABLE_NAME_DATA, ['id' => $id]);
            $this->connection->delete(self::TABLE_NAME_DOCUMENT_PATH, ['data_id' => $id]);
            $this->saveDocumentAndPaths($document, $id);
            $this->connection->commit();

            return true;
        } catch (Exception $exception) {
            $this->connection->rollBack();

            return false;
        }
    }

    /**
     * @param array $document
     * @param       $documentId
     */
    private function saveDocumentAndPaths(array $document,
                                          $documentId): void
    {
        $this->connection->insert(self::TABLE_NAME_DATA, [
            'id'   => $documentId,
            'data' => serialize($document),
        ]);

        $pathGenerator = new ArrayPathGenerator();
        $generatedPaths = $pathGenerator->generate($document);
        foreach ($generatedPaths as $path => $value) {
            $this->connection->insert(self::TABLE_NAME_DOCUMENT_PATH, [
                'id'        => Uuid::uuid4(),
                'data_id'   => $documentId,
                'path'      => $path,
                'value_raw' => $value,
            ]);
        }
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function searchById(string $id): ?array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->from(self::TABLE_NAME_DATA, 'dataTable')
                     ->select('dataTable.*')
                     ->where('dataTable.id = :id')
                     ->setParameter('id', $id)
                     ->setMaxResults(1);
        $queryResult = $queryBuilder->execute()
                                    ->fetchAll();
        $columinizedResult = array_column($queryResult, 'data');
        $result = array_map('unserialize', $columinizedResult);

        return count($result) ? reset($result) : null;
    }
}