# Just Simple Php DataBase

1) Plain text file fast data base
2) Cache
3) Indexing in RAM
4) Search, Sort, aggregate, paginate functions
5) Backup and Restor included
6) Data validation included

# Description
This code is a PHP implementation of a simple flat file database system. It allows you to perform CRUD (Create, Read, Update, Delete) operations on data stored in flat files.

The code consists of several functions:

1. load_data(): This function loads data from the database files into the $db_cache array. It reads each line of the file, decodes it from JSON format, and stores it in the appropriate nested array structure based on the ID.

2. save_data(): This function saves the data from the $db_cache array back into the database files. It flattens the nested array structure using the flattenArray() helper function, then writes each record as a line of JSON data into the corresponding file.

3. flattenArray(): This helper function recursively flattens a nested array into a single-level associative array. It prefixes each key with the parent keys separated by a dot (.).

4. insert_record(): This function inserts a new record into the database. It checks if the ID already exists in the $db_cache array and returns false if it does. Otherwise, it adds the new record to the $db_cache array, saves the data to the files using save_data(), and returns true.

5. update_record(): This function updates an existing record in the database. It checks if the ID exists in the $db_cache array and returns false if it doesn't. Otherwise, it updates the record in the $db_cache array, saves the data to the files using save_data(), and returns true.

6. delete_record(): This function deletes a record from the database. It checks if the ID exists in the $db_cache array and returns false if it doesn't. Otherwise, it removes the record from the $db_cache array, saves the data to the files using save_data(), and returns true.

7. search_records(): This function searches for records in the database based on a specific field and value. It returns an associative array of matching records.

8. sort_records(): This function sorts the records in the database based on a specific field and order. It returns a nested array of sorted records.

9. aggregate_records(): This function performs aggregation operations (sum, average, minimum, maximum) on a specific field in the database. It returns the result of the aggregation operation.

10. paginate_records(): This function provides pagination for the records in the database. It takes a page number and a limit as parameters and returns a nested array of records for the specified page.

11. backup_database(): This function creates a backup of the database files by copying them to a backup directory with a timestamp appended to the filename.

12. restore_database(): This function restores the database from backup files by copying them back to the original database directory.

13. latest_backup(): This function finds the latest backup file for a given database file.

14. validate_data(): This function is a placeholder for additional data validation logic. It currently allows any plain text data without any validation.

15. create_index(): This function creates an index on a specific field in the database. It returns a nested array structure where the keys are unique field values and the values are arrays of records.

The code also includes several helper functions (issetValue(), setValue(), unsetValue(), getValue()) that perform operations on nested arrays, such as checking if a key exists, setting a value, unsetting a value, and getting a value.

# In the bulk versions of the functions:
1. bulk_insert_records takes an array of records as input and attempts to insert each record into the database. It returns the number of successful insertions and an array of records that failed to be inserted.
2. bulk_update_records takes an array of records as input and attempts to update each record in the database. It returns the number of successful updates and an array of records that failed to be updated.
3. bulk_delete_records takes an array of record IDs as input and attempts to delete each record from the database. It returns the number of successful deletions and an array of record IDs that failed to be deleted.
4. bulk_load_data takes an array of records as input and loads them into the cache in a bulk manner. This can be more efficient than loading data from files individually.


**#usage
1) Create directory for storage out of "web" folder and modify $db_directory = '/YOUR_DIR/';
2) include 'jspdb.php'; //in your php file
3) insert_record($id, $data)
4) update_record($id, $data)
5) delete_record($id)
6) search_records($field, $value)
7) sort_records($field, $order = 'asc')
8) aggregate_records($field, $operation) - sum, average, minimum, maximum
9) paginate_records($page, $limit = 10)
10) backup_database()
11) restore_database()
12) latest_backup($db_file)
13) create_index($field) - usefull for tables and another views

Usage examples will be available later
