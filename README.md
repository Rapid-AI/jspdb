# Just Simple Php DataBase

1) Plain text file fast data base
2) Cache
3) Indexing in RAM
4) Search, Sort, aggregate, paginate functions
5) Backup and Restor included
6) Data validation included

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
