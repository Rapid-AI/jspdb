<?

class jspdb
{
    private $db_directory;
    private $db_cache;
    private $cache_initialized;

    public function __construct($db_directory)
    {
        $this->db_directory = $db_directory;
        $this->db_cache = [];
        $this->cache_initialized = false;
    }
  
  	private function acquireLock($handle, $lockType)
    {
        flock($handle, $lockType);
    }

    private function releaseLock($handle)
    {
        flock($handle, LOCK_UN);
    }

    private function flattenArray($array)
    {
        $result = [];
        $stack = [[$array, '']];

        while (!empty($stack)) {
            [$data, $prefix] = array_pop($stack);

            foreach ($data as $key => $value) {
                $newKey = ($prefix ? $prefix . '.' . $key : $key);

                if (is_array($value)) {
                    $stack[] = [$value, $newKey];
                } else {
                    $result[$newKey] = $value;
                }
            }
        }

        return $result;
    }

    private function issetValue(&$array, $key)
    {
        $keys = explode('.', $key);
        $data = &$array;

        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                return false;
            }

            $data = &$data[$key];
        }

        return true;
    }

    private function setValue(&$array, $key, $value)
    {
        $keys = explode('.', $key);
        $data = &$array;

        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                $data[$key] = [];
            }

            $data = &$data[$key];
        }

        $data = $value;
    }

    private function unsetValue(&$array, $key)
    {
        $keys = explode('.', $key);
        $data = &$array;

        foreach ($keys as $key) {
            $data = &$data[$key];
        }

        unset($data);
    }

    private function getValue($array, $key)
    {
        $keys = explode('.', $key);
        $data = $array;

        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                return null;
            }

            $data = $data[$key];
        }

        return $data;
    }

    public function load()
    {
        if (!$this->cache_initialized) {
            $files = glob($this->db_directory . '*.jspdb');

            foreach ($files as $file) {
                $handle = fopen($file, 'r');

                if ($handle) {
                    $this->acquireLock($handle, LOCK_SH);

                    while (!feof($handle)) {
                        $line = fgets($handle);
                        $data = json_decode($line, true);

                        if ($data) {
                            $id = $data['id'];
                            $this->setValue($this->db_cache, $id, $data);
                        }
                    }

                    $this->releaseLock($handle);
                    fclose($handle);
                }
            }

            $this->cache_initialized = true;
        }
    }

    public function save()
    {
        $flattened_data = $this->flattenArray($this->db_cache);

        foreach ($flattened_data as $id => $data) {
            $file_path = $this->db_directory . str_replace('.', '/', $id) . '.jspb';
            $handle = fopen($file_path, 'w');

            if ($handle) {
                $this->acquireLock($handle, LOCK_EX);

                fwrite($handle, json_encode($data) . "\n");
                fclose($handle);

                $this->releaseLock($handle);
            }
        }
    }

    public function insertRecord($id, $data)
    {
        if ($this->issetValue($this->db_cache, $id)) {
            return false;
        }

        $this->setValue($this->db_cache, $id, $data);

        return true;
    }

    public function updateRecord($id, $data)
    {
        if (!$this->issetValue($this->db_cache, $id)) {
            return false;
        }

        $this->setValue($this->db_cache, $id, $data);

        return true;
    }

    public function deleteRecord($id)
    {
        if (!$this->issetValue($this->db_cache, $id)) {
            return false;
        }

        $this->unsetValue($this->db_cache, $id);

        return true;
    }

    public function bulkInsertRecords($records)
    {
        $success_count = 0;
        $failed_records = [];

        foreach ($records as $record) {
            $id = $record['id'];
            $data = $record['data'];

            if (!$this->issetValue($this->db_cache, $id)) {
                $this->setValue($this->db_cache, $id, $data);
                $success_count++;
            } else {
                $failed_records[] = $record;
            }
        }

        return [
            'success_count' => $success_count,
            'failed_records' => $failed_records,
        ];
    }

    public function bulkUpdateRecords($records)
    {
        $success_count = 0;
        $failed_records = [];

        foreach ($records as $record) {
            $id = $record['id'];
            $data = $record['data'];

            if ($this->issetValue($this->db_cache, $id)) {
                $this->setValue($this->db_cache, $id, $data);
                $success_count++;
            } else {
                $failed_records[] = $record;
            }
        }

        return [
            'success_count' => $success_count,
            'failed_records' => $failed_records,
        ];
    }

    public function bulkDeleteRecords($ids)
    {
        $success_count = 0;
        $failed_ids = [];

        foreach ($ids as $id) {
            if ($this->issetValue($this->db_cache, $id)) {
                $this->unsetValue($this->db_cache, $id);
                $success_count++;
            } else {
                $failed_ids[] = $id;
            }
        }

        return [
            'success_count' => $success_count,
            'failed_ids' => $failed_ids,
        ];
    }

    public function searchRecords($field, $value)
    {
        $results = [];

        $this->findValues($this->db_cache, $field, $value, $results);

        return $results;
    }

    private function findValues($array, $field, $value, &$results)
    {
        if (!is_array($array)) {
            return;
        }

        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $this->findValues($val, $field, $value, $results);
            } elseif ($key === $field && $val === $value) {
                $results[$array['id']] = $array;
            }
        }
    }

    public function sortRecords($field, $order = 'asc')
    {
        if (!$this->issetValue($this->db_cache, $field)) {
            return false;
        }

        $sort_order = ($order == 'desc') ? SORT_DESC : SORT_ASC;

        $sorted_data = array_column($this->flattenArray($this->db_cache), null, $field);

        array_multisort(array_column($sorted_data, $field), $sort_order, $sorted_data);

        $nested_data = [];
        foreach ($sorted_data as $id => $data) {
            $this->setValue($nested_data, $id, $data);
        }

        return $nested_data;
    }

    public function aggregateRecords($field, $operation)
    {
        if (!$this->issetValue($this->db_cache, $field)) {
            return false;
        }

        $values = array_column($this->flattenArray($this->db_cache), $field);

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
                return false;
        }
    }

    public function paginateRecords($page, $limit = 10)
    {
        $flattened_data = $this->flattenArray($this->db_cache);
        $total_records = count($flattened_data);
        $total_pages = ceil($total_records / $limit);

        if ($page < 1 || $page > $total_pages) {
            return false;
        }

        $start = ($page - 1) * $limit;
        $end = $start + $limit;

        $paged_data = array_slice($flattened_data, $start, $limit);

        $nested_data = [];
        foreach ($paged_data as $id => $data) {
            $this->setValue($nested_data, $id, $data);
        }

        return $nested_data;
    }

public function backupDatabase() {
        if (!is_dir($this->db_directory)) {
            mkdir($this->db_directory, 0755, true);
        }

        $backup_directory = $this->db_directory . 'backup/';
        if (!is_dir($backup_directory)) {
            mkdir($backup_directory, 0755, true);
        }

        $backup_file = $backup_directory . 'backup_' . date('Y-m-d_H-i-s') . '.json';

        $data = $this->db_cache;

        $fp = fopen($backup_file, 'w');
        fwrite($fp, json_encode($data));
        fclose($fp);

        return $backup_file;
    }

    public function restoreDatabase($backup_file) {
        if (!is_file($backup_file)) {
            return false;
        }

        $this->clearCache();

        $data = file_get_contents($backup_file);
        $imported_data = json_decode($data, true);

        if (is_array($imported_data)) {
            $this->db_cache = $imported_data;
            $this->cache_initialized = true;
            return true; 
        }

        return false;
    }

    private function deleteDirectory($dir) {
        if (!$dh = @opendir($dir)) {
            return;
        }

        while (false !== ($obj = readdir($dh))) {
            if ($obj === '.' || $obj === '..') {
                continue;
            }

            if (!@unlink($dir . '/' . $obj)) {
                $this->deleteDirectory($dir . '/' . $obj);
            }
        }

        closedir($dh);
        @rmdir($dir);
    }

    public function clearCache()
    {
        $this->db_cache = [];
        $this->cache_initialized = false;
    }

    public function countRecords()
    {
        $flattened_data = $this->flattenArray($this->db_cache);
        return count($flattened_data);
    }

    public function getRecordIds()
    {
        $flattened_data = $this->flattenArray($this->db_cache);
        return array_keys($flattened_data);
    }

    public function getRecordKeys($id)
    {
        if (!$this->issetValue($this->db_cache, $id)) {
            return false;
        }

        $flattened_data = $this->flattenArray($this->db_cache[$id]);
        return array_keys($flattened_data);
    }

    public function getRecordValues($id)
    {
        if (!$this->issetValue($this->db_cache, $id)) {
            return false;
        }

        $flattened_data = $this->flattenArray($this->db_cache[$id]);
        return array_values($flattened_data);
    }

    public function getFieldDistinctValues($field)
    {
        $distinct_values = [];

        $this->findDistinctValues($this->db_cache, $field, $distinct_values);

        return $distinct_values;
    }

    private function findDistinctValues($array, $field, &$distinct_values)
    {
        if (!is_array($array)) {
            return;
        }

        foreach ($array as $key => $value) {
            if ($key === $field) {
                if (!in_array($value, $distinct_values)) {
                    $distinct_values[] = $value;
                }
            }

            if (is_array($value)) {
                $this->findDistinctValues($value, $field, $distinct_values);
            }
        }
    }

    public function flushDatabase()
    {
        $this->clearCache();

        $files = glob($this->db_directory . '*.jspb');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    public function getFieldRecords($field, $value)
    {
        $field_records = [];

        $this->findFieldRecords($this->db_cache, $field, $value, $field_records);

        return $field_records;
    }

    private function findFieldRecords($array, $field, $value, &$field_records)
    {
        if (!is_array($array)) {
            return;
        }

        foreach ($array as $key => $record) {
            if (isset($record[$field]) && $record[$field] === $value) {
                $field_records[] = $record;
            }

            if (is_array($record)) {
                $this->findFieldRecords($record, $field, $value, $field_records);
            }
        }
    }

    public function exportDatabase($file_path)
    {
        $this->save();

        $export_data = json_encode($this->db_cache);

        if (file_put_contents($file_path, $export_data)) {
            return true;
        }

        return false;
    }

    public function importDatabase($file_path)
    {
        if (!file_exists($file_path)) {
            return false;
        }

        $import_data = file_get_contents($file_path);
        $imported_db_cache = json_decode($import_data, true);

        if (is_array($imported_db_cache)) {
            $this->db_cache = $imported_db_cache;
            $this->cache_initialized = true;
            return true;
        }

        return false;
    }

    public function renameField($id, $oldField, $newField)
    {
        if (!$this->issetValue($this->db_cache, $id) || !$this->issetValue($this->db_cache[$id], $oldField)) {
            return false;
        }

        $value = $this->getValue($this->db_cache[$id], $oldField);
        $this->unsetValue($this->db_cache[$id], $oldField);
        $this->setValue($this->db_cache[$id], $newField, $value);

        return true;
    }

    public function renameRecordKey($oldId, $newId)
    {
        if (!$this->issetValue($this->db_cache, $oldId)) {
            return false;
        }

        $record = $this->db_cache[$oldId];
        $this->unsetValue($this->db_cache, $oldId);
        $this->setValue($this->db_cache, $newId, $record);

        return true;
    }

    public function incrementFieldValue($id, $field, $amount = 1)
    {
        if (!$this->issetValue($this->db_cache, $id) || !$this->issetValue($this->db_cache[$id], $field)) {
            return false;
        }

        $value = $this->getValue($this->db_cache[$id], $field);
        if (!is_numeric($value)) {
            return false;
        }

        $value += $amount;
        $this->setValue($this->db_cache[$id], $field, $value);

        return true;
    }

    public function decrementFieldValue($id, $field, $amount = 1)
    {
        return $this->incrementFieldValue($id, $field, -$amount);
    }

    public function sortByField($field, $order = 'asc')
    {
        $this->validateSortOrder($order);

        $sorted_data = $this->db_cache;
        uasort($sorted_data, function ($a, $b) use ($field, $order) {
            $valueA = $this->getValue($a, $field);
            $valueB = $this->getValue($b, $field);

            if ($valueA == $valueB) {
                return 0;
            }

            if ($order === 'asc') {
                return ($valueA < $valueB) ? -1 : 1;
            } else {
                return ($valueA > $valueB) ? -1 : 1;
            }
        });

        return $sorted_data;
    }

    private function validateSortOrder($order)
    {
        $validOrders = ['asc', 'desc'];
        if (!in_array($order, $validOrders)) {
            throw new InvalidArgumentException("Invalid sort order. Valid values are 'asc' or 'desc'.");
        }
    }

    public function searchByField($field, $value)
    {
        $matching_records = [];

        $this->findMatchingRecords($this->db_cache, $field, $value, $matching_records);

        return $matching_records;
    }

    private function findMatchingRecords($array, $field, $value, &$matching_records)
    {
        if (!is_array($array)) {
            return;
        }

        foreach ($array as $record) {
            if (isset($record[$field]) && $record[$field] === $value) {
                $matching_records[] = $record;
            }

            foreach ($record as $key => $value) {
                if (is_array($value)) {
                    $this->findMatchingRecords($value, $field, $value, $matching_records);
                }
            }
        }
    }

    public function exportCsv($file_path)
    {
        $this->save();

        $file = fopen($file_path, 'w');

        if (!$file) {
            return false;
        }

        $header = [];
        $data = [];

        // Extract header fields from the first record in the database cache
        if (!empty($this->db_cache)) {
            $header = array_keys(reset($this->db_cache));
        }

        // Extract data rows from each record in the database cache
        foreach ($this->db_cache as $record) {
            $data[] = array_values($record);
        }

        fputcsv($file, $header);
        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);

        return true;
    }

    public function importCsv($file_path, $has_header_row = true)
    {
        if (!file_exists($file_path)) {
            return false;
        }

        $file = fopen($file_path, 'r');

        if (!$file) {
            return false;
        }

        // Clear existing database cache
        $this->clearCache();

        $row = 1;
        while (($data = fgetcsv($file)) !== false) {
            // Skip the header row if it exists
            if ($has_header_row && $row === 1) {
                $row++;
                continue;
            }

            $num_fields = count($data);
            if ($num_fields > 0) {
                $record = [];
                for ($i = 0; $i < $num_fields; $i++) {
                    $record[$i] = $data[$i];
                }
                $this->db_cache[] = $record;
            }

            $row++;
        }

        fclose($file);

        return true;
    }

    public function getRecordCount()
    {
        return count($this->db_cache);
    }

    public function getRecordsInRange($start, $end)
    {
        if ($end < $start || $start < 0 || $end >= $this->getRecordCount()) {
            return [];
        }

        return array_slice($this->db_cache, $start, $end - $start + 1);
    }

    public function getRandomRecord()
    {
        $record_count = $this->getRecordCount();
        if ($record_count === 0) {
            return null;
        }

        $random_index = rand(0, $record_count - 1);
        return $this->db_cache[$random_index];
    }

    public function getRecordById($id)
    {
        return $this->issetValue($this->db_cache, $id) ? $this->db_cache[$id] : null;
    }

}

?>
