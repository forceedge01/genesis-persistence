<?php

namespace Genesis\Services\Persistence;

use PDO;
use Exception;

/**
 * DatabaseService class.
 */
class DatabaseService implements Contracts\StoreInterface
{
    public $connection;

    public function __construct(array $params)
    {
        if (! $this->connection) {
            $this->connection = new PDO($this->getConnectionString($params));
        }
    }

    private function getConnectionString(array $params)
    {
        switch ($params['databaseEngine']) {
            case 'sqlite':
                return "sqlite:{$params['path']}";

            default:
                throw new Exception("Database {$params['databaseEngine']} is not supported at the moment.");
        }
    }

    public function save($table, array $values)
    {
        list($columns, $values) = $this->getValuesClauseFromArray($values);
        $query =  "INSERT INTO `$table` $columns VALUES $values";
        $this->execute($query);

        return $this->connection->lastInsertId();
    }

    public function execute($query)
    {
        $statement = $this->connection->query($query);
        $this->checkForErrors($query);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function get($table, array $where)
    {
        $whereClause = $this->getWhereClauseFromArray($where);
        $query =  "SELECT * FROM `$table` WHERE $whereClause";

        return $this->execute($query);
    }

    public function getAll($table)
    {
        $query =  "SELECT * FROM `$table`";

        return $this->execute($query);
    }

    public function getSingle($table, array $where = [], $order = 'asc')
    {
        $whereClause = '';
        $firstColumn = '';

        if ($where) {
            $whereClause = 'WHERE ' . $this->getWhereClauseFromArray($where);
            $firstColumn = 'ORDER BY ' . array_keys($where)[0] . ' ' . $order;
        } else {
            $firstColumn = 'ORDER BY `id` ' . $order;
        }

        $query =  "SELECT * FROM `$table` $whereClause {$firstColumn} LIMIT 1";
        $result = $this->execute($query);

        if (isset($result[0])) {
            return $result[0];
        }

        return false;
    }

    public function delete($table, array $where = [])
    {
        $whereClause = '';

        if ($where) {
            $whereClause = 'WHERE ' . $this->getWhereClauseFromArray($where);
        }

        $query =  "DELETE FROM `$table` $whereClause";
        $this->execute($query);

        return $this;
    }

    public function update($table, array $update, array $where = [])
    {
        $updateClause = $this->getUpdateClauseFromArray($update);

        if ($where) {
            $whereClause = 'WHERE ' . $this->getWhereClauseFromArray($where);
        }

        $query = "UPDATE $table SET $updateClause $whereClause";
    }

    private function checkForErrors($query)
    {
        if ($this->connection->errorCode() !== '00000') {
            $prex = function () { echo '<pre>'; $args = func_get_args(); foreach ($args as $key => $arg) {
     var_dump($arg);
     echo PHP_EOL . '===============================' . PHP_EOL . PHP_EOL;
 } echo 'Debug backtrace ' . PHP_EOL; print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)); echo 'Output from: ' . __FILE__ . ', Line: ' . __LINE__; exit; };
            $prex(
                $this->connection->errorInfo(), $query
                // ,get_class($this), get_class_methods($this)
            );
        }
    }

    private function getWhereClauseFromArray(array $whereArray)
    {
        $where = '';

        foreach ($whereArray as $column => $value) {
            $value = $this->quoteValue($value);
            $where .= "`$column` = $value AND ";
        }

        $where = rtrim($where, 'AND ');

        return $where;
    }

    private function getUpdateClauseFromArray(array $updateArray)
    {
        $update = '';

        foreach ($updateArray as $column => $value) {
            $value = $this->quoteValue($value);
            $update .= "`$column` = $value, ";
        }

        $update = rtrim($update, ', ');

        return $update;
    }

    private function quoteValue($value)
    {
        $excludePattern = '/^(null)|(count\(.+\))|(sum\(.+\))|date\(.+\)|now\(\)$/';

        $value = (is_numeric($value) || preg_match($excludePattern, strtolower($value))) ? $value : "'$value'";

        return $value;
    }

    private function getValuesClauseFromArray(array $values)
    {
        $columns = '(';
        $columnValues = '(';

        foreach ($values as $column => $value) {
            $columns .= '`' . $column . '`, ';
            $value = $this->quoteValue($value);
            $columnValues .= "$value, ";
        }

        $columns = substr($columns, 0, -2);
        $columnValues = substr($columnValues, 0, -2);

        $columns .= ')';
        $columnValues .= ')';

        return [$columns, $columnValues];
    }
}
