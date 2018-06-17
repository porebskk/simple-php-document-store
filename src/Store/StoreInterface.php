<?php

namespace SimplePhpDocumentStore\Store;

use SimplePhpDocumentStore\Query\Query;

interface StoreInterface
{
    public function initDataStructure();

    /**
     * @param array $document
     *
     * @return mixed Unique Database-ID for the document.
     */
    public function store(array $document);

    /**
     * @param string $id
     *
     * @return array
     */
    public function searchById(string $id): ?array;

    public function searchByQuery(Query $query): array;

    /**
     * Updates a document.
     *
     * @param string $id Unique Database-ID for the document.
     * @param array  $document
     *
     * @return bool On success return true, on failure return false
     */
    public function update(string $id,
                           array $document): bool;
}