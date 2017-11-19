<?php

namespace Genesis\Services\Persistence;

use Exception;
use PDO;

/**
 * DatabaseService class.
 */
class DatabaseService implements Contracts\StoreInterface
{
    public $connection;

    public $params = [];

    public function __construct(array $params)
    {
        $this->params = $params;

        if (! $this->connection) {
            $username = $password = null;
            $options = [];

            if (isset($params['username'])) {
                $username = $params['username'];
            }

            if (isset($params['password'])) {
                $password = $params['password'];
            }

            if (isset($params['options'])) {
                $options = $params['options'];
            }

            $this->connection = new PDO($this->getConnectionString($params), $username, $password, $options);
        }
    }

    /**
     * Save values to table.
     *
     * @param string $table The table to save values into.
     * @param array $values The values to be saved.
     *
     * @return int
     */
    public function save($table, array $values)
    {
        list($columns, $values) = $this->getValuesClauseFromArray($values);
        $query =  "INSERT INTO `$table` $columns VALUES $values";
        $this->execute($query);

        return $this->connection->lastInsertId();
    }

    /**
     * Execute sql query.
     *
     * @param string $query The query to execute.
     *
     * @return array
     */
    public function execute($query)
    {
        $statement = $this->connection->prepare($query);
        $this->checkForErrors($query);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * Get data by clause.
     *
     * @param string $table The table to get the data from.
     * @param array $where The criteria.
     * @param array $order The order clause.
     *
     * @return array
     */
    public function get($table, array $where, array $order = ['id' => 'asc'])
    {
        $whereClause = $this->getWhereClauseFromArray($where);
        $orderClause = $this->getOrderClause($order);
        $query =  "SELECT * FROM `$table` $whereClause $orderClause";

        return $this->execute($query);
    }

    /**
     * Get data by clause.
     *
     * @param string $table The table to get the data from.
     * @param array $order The order clause.
     *
     * @return array
     */
    public function getAll($table, array $order = ['id' => 'asc'])
    {
        $orderClause = $this->getOrderClause($order);
        $query =  "SELECT * FROM `$table` $orderClause";

        return $this->execute($query);
    }

    /**
     * Get data by clause.
     *
     * @param string $table The table to get the data from.
     * @param array $where The criteria.
     *
     * @return array
     */
    public function getCount($table, array $where)
    {
        $whereClause = $this->getWhereClauseFromArray($where);
        $query = "SELECT count(id) AS {$table}Count FROM `$table` $whereClause";

        return $this->execute($query);
    }

    /**
     * Get a single record or false if not found.
     *
     * @param string $table The table to get the data from.
     * @param array $where The criteria.
     * @param array $order The order clause.
     *
     * @return array|false
     */
    public function getSingle($table, array $where = [], array $order = ['id' => 'asc'])
    {
        $whereClause = '';
        $orderBy = '';

        if ($where) {
            $whereClause = $this->getWhereClauseFromArray($where);
        }

        if ($order) {
            $orderBy = $this->getOrderClause($order);
        }

        $query =  "SELECT * FROM `$table` $whereClause {$orderBy} LIMIT 1";
        $result = $this->execute($query);

        if (isset($result[0])) {
            return $result[0];
        }

        return false;
    }

    /**
     * Delete data.
     *
     * @param string $table The table to get the data from.
     * @param array $where The criteria.
     *
     * @return $this
     */
    public function delete($table, array $where = [])
    {
        $whereClause = '';

        if ($where) {
            $whereClause = $this->getWhereClauseFromArray($where);
        }

        $query =  "DELETE FROM `$table` $whereClause";
        $this->execute($query);

        return $this;
    }

    /**
     * Update table data.
     *
     * @param string $table The table to get the data from.
     * @param array $update The values to update.
     * @param array $where The criteria.
     *
     * @return $this
     */
    public function update($table, array $update, array $where = [])
    {
        $updateClause = $this->getUpdateClauseFromArray($update);

        if ($where) {
            $whereClause = $this->getWhereClauseFromArray($where);
        }

        $query = "UPDATE $table SET $updateClause $whereClause";
        $this->execute($query);

        return $this;
    }

    /**
     * Get order by clause as a string.
     *
     * @param array $order The order clause.
     *
     * @return string
     */
    public function getOrderClause(array $order)
    {
        $column = key($order);
        $value = current($order);

        return "ORDER BY `$column` $value";
    }

    /**
     * Get where clause as string.
     *
     * @param array $whereArray The criteria.
     *
     * @return string
     */
    public function getWhereClauseFromArray(array $whereArray)
    {
        $where = '';

        foreach ($whereArray as $column => $value) {
            $value = $this->quoteValue($value);
            $where .= "`$column` = $value AND ";
        }

        $where = rtrim($where, 'AND ');

        return 'WHERE ' . $where;
    }

    /**
     * Get update clause as string.
     *
     * @param array $updateArray The update clause.
     *
     * @return string
     */
    public function getUpdateClauseFromArray(array $updateArray)
    {
        $update = '';

        foreach ($updateArray as $column => $value) {
            $value = $this->quoteValue($value);
            $update .= "`$column` = $value, ";
        }

        $update = rtrim($update, ', ');

        return $update;
    }

    /**
     * Get values clause.
     *
     * @param array $values The values to get.
     *
     * @return array
     */
    public function getValuesClauseFromArray(array $values)
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

    private function getConnectionString(array $params)
    {
        if (! isset($params['dbengine'])) {
            throw new Exception('The database engine must be specified.');
        }

        switch ($params['dbengine']) {
            case 'sqlite':
                return "sqlite:{$params['path']}";
            case 'mysql':
                if (! isset($params['port'])) {
                    $params['port'] = 3306;
                }

                return "mysql:dbname={$params['dbname']};host={$params['host']};port={$params['port']}";
            case 'pgsql':
                if (! isset($params['port'])) {
                    $params['port'] = 5432;
                }

                return "pgsql:dbname={$params['dbname']};host={$params['host']};port={$params['port']};sslmode={$params['sslmode']}";
            default:
                throw new Exception("Database {$params['dbengine']} is not supported at the moment.");
        }
    }

    private function checkForErrors($query)
    {
        if ($this->connection->errorCode() !== '00000') {
            $prex = function () {
                echo '<pre>';
                $args = func_get_args();
                foreach ($args as $key => $arg) {
                    var_dump($arg);
                    echo PHP_EOL . '===============================' . PHP_EOL . PHP_EOL;
                }
                echo 'Debug backtrace ' . PHP_EOL;
                print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5));
                echo 'Output from: ' . __FILE__ . ', Line: ' . __LINE__;
                exit;
            };
            $prex(
                $this->connection->errorInfo(), $query
                // ,get_class($this), get_class_methods($this)
            );
        }
    }

    private function quoteValue($value)
    {
        $excludePattern = '/^(null)|(count\(.+\))|(sum\(.+\))|date\(.+\)|now\(\)$/';

        if ((is_numeric($value) || preg_match($excludePattern, strtolower($value)))) {
            $value = $value;
        } else {
            $value = '\'' . str_replace("'", "''", $value) . '\'';
        }

        return $value;
    }
}
