# SimplePhpDocumentStore
SimplePhpDocumentStore is a simple NoSQL database for storing php arrays and
querying them. The Querying part is solved by doing extra lifting when the
documents are stored in the database (storing all unique paths as separate
values).

## Installation
`composer install porebskk/simple-php-document-store`

## Features
* A variety of database platforms are supported via [DBAL](https://www.doctrine-project.org/projects/doctrine-dbal/en/2.7/reference/platforms.html#platforms)
* JSON-Path Query Builder

## Usage
### Initialization
```php
//Setting up the DbalAdapter, we are using here Sqlite in memory mode
$connectionParams = ['url' => 'sqlite:///:memory:'];
$conn = DriverManager::getConnection($connectionParams);
$conn->exec('PRAGMA foreign_keys = ON');
$dbalAdapter = new DbalAdapter($conn);

//this is only required once for persistence storages
$dbalAdapter->initDataStructure();
```
### Storage
```php
$document = [
    'person' => [
        'head' => [
            'nose' => 'big',
            'eyes' => 'blue',
        ],
    ],
];

$documentId = $dbalAdapter->store($document);
```
### Querying (by ID)
```php
$documentId = $dbalAdapter->store($document);

$document = $dbalAdapter->searchById($documentId);
```
### Querying (with a QueryObject)
```php
$query = new Query();
//Finding document where the JSON Path "person.head.eyes" is either red or orange
$query->whereAnd(
    (new OrStatement())->add(
        new WhereStatement('person.head.eyes', '=', 'red'),
        new WhereStatement('person.head.eyes', '=', 'orange')
    )
);

$documents = $dbalAdapter->searchByQuery($query);

//It is possible to wrap AND/OR Statement as deep as possible
$query->whereAnd(
    (new OrStatement())->add(
        new WhereStatement('person.head.eyes', '=', 'blue'),
        (new AndStatement())->add(
            new WhereStatement('person.head.eyes', '=', 'orange'),
            new WhereStatement('person.character.crazy', '=', 'yes')
        )
    ),
    new WhereStatement('person.feet', '=', 'big')
);
```
### Updating a document
```php
$dbalAdapter->update($documentId, $updatedDocument);
```
