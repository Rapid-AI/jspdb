<?php
// Connect to the flat file database
$db_directory = 'data/';

// Initialize a cache for users data
$db_cache = [];

// Cache initialization flag
$cache_initialized = false;

// Load users data from files into cache
function load_data() {
    global $db_directory, $db_cache, $cache_initialized;

    if (!$cache_initialized) {
        $files = glob($db_directory . '*.jspdb');

        foreach ($files as $file) {
            $handle = fopen($file, 'r');

            if ($handle) {
                while (!feof($handle)) {
                    $line = fgets($handle);
                    $data = json_decode($line, true);

                    if ($data) {
                        $nestedData = &$db_cache;
                        $keys = explode('.', $data['id']);
                        foreach ($keys as $key) {
                            if (!isset($nestedData[$key])) {
                                $nestedData[$key] = [];
                            }
                            $nestedData = &$nestedData[$key];
                        }
                        $nestedData = $data;
                    }
                }

                fclose($handle);
            }
        }

        $cache_initialized = true;
    }
}

// Save users data from cache to files
function save_data() {
    global $db_directory, $db_cache;

    foreach (flattenArray($db_cache) as $id => $data) {
        $file_path = $db_directory . str_replace('.', '/', $id) . '.jspdb';
        $handle = fopen($file_path, 'w');

        if ($handle) {
            fwrite($handle, json_encode($data) . "\n");
            fclose($handle);
        }
    }
}

// Helper function to flatten the nested array
function flattenArray($array, $prefix = '') {
    $result = [];
    foreach ($array as $key => $value) {
        $newKey = ($prefix ? $prefix . '.' . $key : $key);
        if (is_array($value)) {
            $result += flattenArray($value, $newKey);
        } else {
            $result[$newKey] = $value;
        }
    }
    return $result;
}

// Insert a new record into the database
function insert_record($id, $data) {
    global $db_cache;

    // Check if ID already exists
    if (issetValue($db_cache, $id)) {
        return false; // ID already exists, record not inserted
    }

    setValue($db_cache, $id, $data);

    save_data();

    return true; // Record inserted successfully
}

// Update an existing record in the database
function update_record($id, $data) {
    global $db_cache;

    // Check if ID exists
    if (!issetValue($db_cache, $id)) {
        return false; // ID does not exist, record not updated
    }

    setValue($db_cache, $id, $data);

    save_data();

    return true; // Record updated successfully
}

// Delete a record from the database
function delete_record($id) {
    global $db_cache;

    // Check if ID exists
    if (!issetValue($db_cache, $id)) {
        return false; // ID does not exist, record not deleted
    }

    unsetValue($db_cache, $id);

    save_data();

    return true; // Record deleted successfully
}


// Bulk insert records into the database
function bulk_insert_records($records) {
    global $db_cache;

    $success_count = 0;
    $failed_records = [];

    foreach ($records as $record) {
        $id = $record['id'];
        $data = $record['data'];

        if (!issetValue($db_cache, $id)) {
            setValue($db_cache, $id, $data);
            $success_count++;
        } else {
            $failed_records[] = $record;
        }
    }

    save_data();

    return [
        'success_count' => $success_count,
        'failed_records' => $failed_records
    ];
}

// Bulk update records in the database
function bulk_update_records($records) {
    global $db_cache;

    $success_count = 0;
    $failed_records = [];

    foreach ($records as $record) {
        $id = $record['id'];
        $data = $record['data'];

        if (issetValue($db_cache, $id)) {
            setValue($db_cache, $id, $data);
            $success_count++;
        } else {
            $failed_records[] = $record;
        }
    }

    save_data();

    return [
        'success_count' => $success_count,
        'failed_records' => $failed_records
    ];
}

// Bulk delete records from the database
function bulk_delete_records($ids) {
    global $db_cache;

    $success_count = 0;
    $failed_ids = [];

    foreach ($ids as $id) {
        if (issetValue($db_cache, $id)) {
            unsetValue($db_cache, $id);
            $success_count++;
        } else {
            $failed_ids[] = $id;
        }
    }

    save_data();

    return [
        'success_count' => $success_count,
        'failed_ids' => $failed_ids
    ];
}

// Bulk load data into the cache
function bulk_load_data($data) {
    global $db_cache, $cache_initialized;

    if (!$cache_initialized) {
        $nestedData = &$db_cache;

        foreach ($data as $record) {
            $id = $record['id'];
            $data = $record['data'];

            $keys = explode('.', $id);
            foreach ($keys as $key) {
                if (!isset($nestedData[$key])) {
                    $nestedData[$key] = [];
                }
                $nestedData = &$nestedData[$key];
            }
            $nestedData = $data;
        }

        $cache_initialized = true;
    }
}

// Search for values in a nested array based on a key-value pair
function findValues($array, $field, $value) {
    if (!is_array($array)) {
        return [];
    }

    $results = [];

    foreach ($array as $key => $val) {
        if (is_array($val)) {
            $nestedResults = findValues($val, $field, $value);
            $results = array_merge($results, $nestedResults);
        } elseif ($key === $field && $val === $value) {
            $results[$array['id']] = $array;
        }
    }

    return $results;
}

// Search for records in the database
function search_records($field, $value) {
    global $db_cache;

    $results = [];

    foreach (findValues($db_cache, $field, $value) as $id => $data) {
        $results[$id] = $data;
    }

    return $results;
}

// Sort records in the database
function sort_records($field, $order = 'asc') {
    global $db_cache;

    // Check if field exists
    if (!issetValue($db_cache, $field)) {
        return false; // Field does not exist, cannot sort
    }

    $sort_order = ($order == 'desc') ? SORT_DESC : SORT_ASC;

    $sorted_data = array_column(flattenArray($db_cache), null, $field);

    array_multisort(array_column($sorted_data, $field), $sort_order, $sorted_data);

    $nested_data = [];
    foreach ($sorted_data as $id => $data) {
        setValue($nested_data, $id, $data);
    }

    return $nested_data;
}

// Aggregate records in the database
function aggregate_records($field, $operation) {
    global $db_cache;

    // Check if field exists
    if (!issetValue($db_cache, $field)) {
        return false; // Field does not exist, cannot aggregate
    }

    $values = array_column(flattenArray($db_cache), $field);

    switch ($operation) {
        case 'sum':
            return array_sum($values);
        case 'average':
            return array_sum($values) / count($values);
        case 'minimum':
            return min($values);
        case 'maximum':
            return max($values);
        default:
            return false; // Invalid operation
    }
}

// Pagination
function paginate_records($page, $limit = 10) {
    global $db_cache;

    $flattened_data = flattenArray($db_cache);
    $total_records = count($flattened_data);
    $total_pages = ceil($total_records / $limit);

    if ($page < 1 || $page > $total_pages) {
        return false; // Invalid page number
    }

    $start = ($page - 1) * $limit;
    $end = $start + $limit;

    $paged_data = array_slice($flattened_data, $start, $limit);

    $nested_data = [];
    foreach ($paged_data as $id => $data) {
        setValue($nested_data, $id, $data);
    }

    return $nested_data;
}

// Backup the database
function backup_database() {
    global $db_directory;

    // Create a backup directory if it doesn't exist
    if (!is_dir($db_directory)) {
        mkdir($db_directory);
    }

    $timestamp = date('YmdHis');

    $files = glob($db_directory . '*.jspdb');

    foreach ($files as $file) {
        $backup_file = $db_directory . 'backup_' . basename($file, '.jspb') . '_' . $timestamp . '.jspb';

        // Copy the database file to the backup location
        copy($file, $backup_file);
    }

    return true;
}

// Restore the database from backup files
function restore_database() {
    global $db_directory, $db_cache, $cache_initialized;

    $backup_files = glob($db_directory . 'backup_*.jspb');

    foreach ($backup_files as $backup_file) {
        $file_path = $db_directory . basename($backup_file);

        // Check if backup file exists
        if (!file_exists($backup_file)) {
            return false; // Backup file not found
        }

        // Restore the database file from the backup
        copy($backup_file, $file_path);
    }

    // Clear the cache and load data from files
    $db_cache = [];
    $cache_initialized = false;

    load_data();

    return true; // Database restored successfully
}

// Get the latest backup file for a given database file
function latest_backup($db_file) {
    global $db_directory;

    $backup_files = glob($db_directory . 'backup_*_' . $db_file);

    if (empty($backup_files)) {
        return false; // No backup files found
    }

    // Sort the backup files by timestamp in descending order
    rsort($backup_files);

    return $backup_files[0];
}

function validate_data($data) {
    // Convert data to string if it is not already
    if (!is_string($data)) {
        $data = strval($data);
    }

    // Check if the data is a string (plain text)
    if (!is_string($data)) {
        return false;
    }

    // Add any additional validation logic for plain text data

    // Return true if the data meets the required criteria
    return true;
}


// Indexing
function create_index($field) {
    global $db_cache;

    $indexed_data = [];

    foreach (flattenArray($db_cache) as $id => $data) {
        $value = getValue($data, $field);

        if (!isset($indexed_data[$value])) {
            $indexed_data[$value] = [];
        }

        setValue($indexed_data[$value], $id, $data);
    }

    return $indexed_data;
}

// Helper function to check if a key exists in a nested array
function issetValue(&$array, $key) {
    $keys = explode('.', $key);
    foreach ($keys as $key) {
        if (!isset($array[$key])) {
            return false;
        }
        $array = &$array[$key];
    }
    return true;
}

// Helper function to set a value in a nested array
function setValue(&$array, $key, $value) {
    $keys = explode('.', $key);
    $lastKey = array_pop($keys);
    foreach ($keys as $k) {
        if (!isset($array[$k])) { 
            $array[$k] = []; 
        } 
        $array = &$array[$k]; 
    }
    $array[$lastKey] = $value;
}

// Helper function to unset a value in a nested array
function unsetValue(&$array, $key) {
    $keys = explode('.', $key);
    $lastKey = array_pop($keys);
    foreach ($keys as $k) {
        $array = &$array[$k];
    }
    unset($array[$lastKey]);
}

// Helper function to get a value from a nested array 
function getValue($array, $key) {
    $keys = explode('.', $key);
    foreach ($keys as $k) {
        if (!isset($array[$k])) {
            return null;
        }
        $array = $array[$k];
    }
    return $array;
}
