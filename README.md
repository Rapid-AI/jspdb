# Just Simple Php DataBase

You can use it when your hosting is cheap limited or you need simple database with good performance

1) Plain text file fast data base
2) Cache
3) Indexing in RAM
4) Search, Sort, aggregate, paginate functions
5) Backup and Restor included
6) Data validation included

# Description
This class implements a simple flat file database. It stores data in JSON format in individual .jspb files. The data is cached in memory for performance.

- The constructor accepts a database directory where the files will be stored.

```php
public function __construct($db_directory) 
```

- `load()` loads all data files into the in-memory cache.

- `save()` saves the in-memory cache to the data files.

- `insertRecord()`, `updateRecord()` and `deleteRecord()` manipulate single records.

- `bulkInsertRecords()`, `bulkUpdateRecords()` and `bulkDeleteRecords()` operate on multiple records.

- `searchRecords()` searches by a field and value.

- `sortRecords()` sorts the records by a field.

- `aggregateRecords()` performs aggregate functions.

- `paginateRecords()` paginates the records.

- `backupDatabase()` and `restoreDatabase()` backup and restore the database.

- `clearCache()` empties the in-memory cache.

- There are many utility methods to get information from the database:
    - `countRecords()` 
    - `getRecordIds()`
    - `getRecordKeys()`
    - `getRecordValues()`
    - `getFieldDistinctValues()` 
    - etc.

- The database can be exported and imported using `exportDatabase()` and `importDatabase()`.

- Fields can be renamed using `renameField()` and `renameRecordKey()`.

- Records can be sorted, searched, and manipulated in various ways.

- The database can be exported to CSV using `exportCsv()` and imported from CSV using `importCsv()`.

So in summary, this class implements all the basic CRUD operations and more for a simple flat file database. The in-memory caching provides good performance, while storing the actual data in flat files keeps it persistent.


**#usage
1. Creating an instance of the `jspdb` class and initializing it with a directory path:

```php
$database = new jspdb('/path/to/database/directory/');
```

2. Loading the database records from files into the cache:

```php
$database->load();
```

3. Inserting a new record into the database:

```php
$id = 'record1';
$data = ['name' => 'John Doe', 'age' => 30];
$success = $database->insertRecord($id, $data);
if ($success) {
    echo "Record inserted successfully.";
} else {
    echo "Failed to insert record.";
}
```

4. Updating an existing record in the database:

```php
$id = 'record1';
$newData = ['name' => 'Jane Smith', 'age' => 35];
$success = $database->updateRecord($id, $newData);
if ($success) {
    echo "Record updated successfully.";
} else {
    echo "Failed to update record.";
}
```

5. Deleting a record from the database:

```php
$id = 'record1';
$success = $database->deleteRecord($id);
if ($success) {
    echo "Record deleted successfully.";
} else {
    echo "Failed to delete record.";
}
```

6. Searching for records with a specific field value:

```php
$field = 'age';
$value = 30;
$results = $database->searchRecords($field, $value);
echo "Found " . count($results) . " records matching the search criteria.";
```

7. Sorting records by a specific field:

```php
$field = 'name';
$order = 'asc'; // 'asc' for ascending order, 'desc' for descending order
$sortedData = $database->sortRecords($field, $order);
if (!$sortedData) {
    echo "Failed to sort records.";
} else {
    foreach ($sortedData as $id => $record) {
        echo "Record ID: $id, Name: " . $record['name'] . ", Age: " . $record['age'] . "<br>";
    }
}
```

8. Counting the total number of records in the database:

```php
$count = $database->countRecords();
echo "Total records: $count";
```

These are just a few examples of what you can do with the `jspdb` class. You can explore the remaining methods in the class and experiment with different use cases based on your specific requirements.
