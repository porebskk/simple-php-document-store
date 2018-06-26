# SimplePhpDocumentStore
SimplePhpDocumentStore is a simple document store (fancy word for php arrays) that is running 
sql under the hood. It also provides a query component using paths extracted from the document. 
The querying part is solved by doing extra lifting when the
documents are stored in the database (storing all unique paths as separate
values).

## Installation
`composer require porebskk/simple-php-document-store`

## Features
* A variety of database platforms are supported via [DBAL](https://www.doctrine-project.org/projects/doctrine-dbal/en/2.7/reference/platforms.html#platforms)
* JSON-Path Query Builder

## Usage
### Initialization
```php
//Setting up the DbalAdapter, we are using here Sqlite in memory mode
//requires the sqlite extension to be enabled in the used php.ini file
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
//Allowed operators depend on the adapter implementation
//for DBAL see the ExpressionBuilder::* constants
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

### Limit the amount of returned documents
```php
$query = (new Query())->limit(5);

$maximalOf5Documents = $dbalAdapter->searchByQuery($query);
```

### Advanced Usage
Storing documents with array data and querying them:
```php
$document = [
    'friends' => [
        ['person' => ['name' => 'Bob', 'age' => 26]],
        ['person' => ['name' => 'Alice', 'age' => 25]],
    ],
];

$dbalAdapter->store($document);

//Following paths will be extracted from the array:
//'friends.person.name' => 'Bob',
//'friends.person.age' => 26
//'friends.person.name' => 'Alice'
//'friends.person.age' => 25

//Now we query the data
$query = new Query();
$query->whereAnd(
    new WhereStatement('friends.person.age', '<', '30')
);

$documents = $dbalAdapter->searchByQuery($query);
```