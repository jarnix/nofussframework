<?php
namespace Nf\Db\Adapter;

use Nf\Localization;

class Mysqli extends AbstractAdapter
{

    protected $_resourceClass = '\\Nf\\Db\\Resource\\Mysqli';

    protected function _connect()
    {
        if ($this->_connection) {
            return;
        }
        
        if (! extension_loaded('mysqli')) {
            throw new \Exception('The Mysqli extension is required for this adapter but the extension is not loaded');
        }
        
        if (isset($this->_config['port'])) {
            $port = (integer) $this->_config['port'];
        } else {
            $port = null;
        }
        
        $this->_connection = mysqli_init();
        
        if (! empty($this->_config['driver_options'])) {
            foreach ($this->_config['driver_options'] as $option => $value) {
                if (is_string($option)) {
                    // Suppress warnings here
                    // Ignore it if it's not a valid constant
                    $option = @constant(strtoupper($option));
                    if ($option === null) {
                        continue;
                    }
                }
                @mysqli_options($this->_connection, $option, $value);
            }
        }
        
        // Suppress connection warnings here.
        // Throw an exception instead.
        try {
            $_isConnected = mysqli_real_connect($this->_connection, $this->_config['hostname'], $this->_config['username'], $this->_config['password'], $this->_config['database'], $port);
        } catch (Exception $e) {
            $_isConnected = false;
        }
        
        if ($_isConnected === false || mysqli_connect_errno()) {
            $this->closeConnection();
            throw new \Exception(mysqli_connect_error());
        }
        
        if ($_isConnected && ! empty($this->_config['charset'])) {
            mysqli_set_charset($this->_connection, $this->_config['charset']);
        }
    }

    public function isConnected()
    {
        return ((bool) ($this->_connection instanceof mysqli));
    }

    public function closeConnection()
    {
        if ($this->isConnected()) {
            $this->_connection->close();
        }
        $this->_connection = null;
    }

    public function getQuoteIdentifierSymbol()
    {
        return "`";
    }

    protected function _quote($value)
    {
        if (null === $value) {
            return 'NULL';
        } elseif (is_int($value) || is_float($value) || $value instanceof \Nf\Db\Expression) {
            return $value;
        }
        $this->_connect();
        return "'" . $this->_connection->real_escape_string($value) . "'";
    }

    public function lastInsertId($tableName = null, $primaryKey = null)
    {
        $mysqli = $this->_connection;
        return (string) $mysqli->insert_id;
    }

    public function insert($tableName, array $bind)
    {
        $sql = "INSERT INTO " . $this->quoteIdentifier($tableName, true);
        if (! count($bind)) {
            // allows for inserting a row without values to get an auto increment id
            $sql .= " VALUES()";
        } else {
            $sql .= " SET ";
            $insertFields = array();
            foreach ($bind as $key => $value) {
                $insertFields[] = $this->quoteIdentifier($key) . "=" . $this->quote($value);
            }
            $sql .= " " . implode(', ', $insertFields);
        }
        
        $res = new $this->_resourceClass($sql, $this);
        $res->execute();
        
        return $this->getConnection()->affected_rows;
    }

    public function insertIgnore($tableName, array $bind)
    {
        $sql = "INSERT IGNORE INTO " . $this->quoteIdentifier($tableName, true) . " SET ";
        $updateFields = array();
        foreach ($bind as $key => $value) {
            $updateFields[] = $this->quoteIdentifier($key) . "=" . $this->quote($value);
        }
        $sql .= " " . implode(', ', $updateFields);
        
        $res = new $this->_resourceClass($sql, $this);
        $res->execute();
        
        return $this->getConnection()->affected_rows;
    }

    public function upsert($tableName, array $bind, array $where)
    {
        $sql = "INSERT INTO " . $this->quoteIdentifier($tableName, true) . " SET ";
        $updateFields = array();
        foreach ($bind as $key => $value) {
            $updateFields[] = $this->quoteIdentifier($key) . "=" . $this->quote($value);
        }
        foreach ($where as $key => $value) {
            $updateFields[] = $this->quoteIdentifier($key) . "=" . $this->quote($value);
        }
        $sql .= " " . implode(', ', $updateFields);
        
        $sqlOnDuplicate = " ON DUPLICATE KEY UPDATE ";
        $onDuplicateFields = array();
        foreach ($bind as $key => $value) {
            $onDuplicateFields[] = $this->quoteIdentifier($key) . "=" . $this->quote($value);
        }
        $sqlOnDuplicate .= " " . implode(', ', $onDuplicateFields);
        
        $sql .= $sqlOnDuplicate;
        
        $res = new $this->_resourceClass($sql, $this);
        $res->execute();
        
        return $this->getConnection()->affected_rows;
    }

    public function update($tableName, array $bind, $where = '')
    {
        $sql = "UPDATE " . $this->quoteIdentifier($tableName, true) . " SET ";
        $updateFields = array();
        foreach ($bind as $key => $value) {
            $updateFields[] = $this->quoteIdentifier($key) . "=" . $this->quote($value);
        }
        $sql .= " " . implode(', ', $updateFields);
        if (!is_array($where)) {
            if ($where != '') {
                $sql .= " WHERE " . $where;
            }
        } else {
            $whereFields = array();
            foreach ($where as $key => $value) {
                $whereFields[] = $this->quoteIdentifier($key) . "=" . $this->quote($value);
            }
            $sql .= " WHERE " . implode(', ', $whereFields);
        }
        $res = new $this->_resourceClass($sql, $this);
        $res->execute();
        
        return $this->getConnection()->affected_rows;
    }

    public function delete($tableName, $where = null)
    {
        if ($where !== null) {
            $sql = "DELETE FROM " . $this->quoteIdentifier($tableName, true);
            if (!is_array($where)) {
                if ($where != '') {
                    $sql .= " WHERE " . $where;
                }
            } else {
                $whereFields = array();
                foreach ($where as $key => $value) {
                    $whereFields[] = $this->quoteIdentifier($key) . "=" . $this->quote($value);
                }
                $sql .= " WHERE " . implode(' AND ', $whereFields);
            }
        } else {
            $sql = "TRUNCATE TABLE" . $this->quoteIdentifier($tableName, true);
        }
        
        $res = new $this->_resourceClass($sql, $this);
        $res->execute();
        
        return $this->getConnection()->affected_rows;
    }

    function cleanConnection()
    {
        $mysqli = $this->_connect();
        $mysqli = $this->_connection;
        
        while ($mysqli->more_results()) {
            if ($mysqli->next_result()) {
                $res = $mysqli->use_result();
                if (is_object($res)) {
                    $res->free_result();
                }
            }
        }
    }

    public function multiQuery($queries)
    {
        $mysqli = $this->_connect();
        $mysqli = $this->_connection;
        
        if (is_array($queries)) {
            $queries = implode(';', $queries);
        }
        
        $ret = $mysqli->multi_query($queries);
        
        if ($ret === false) {
            throw new \Exception($mysqli->error);
        }
    }

    public static function formatDate($inShortFormatDateOrTimestamp, $hasMinutes = false)
    {
        $tstp = Localization::dateToTimestamp($inShortFormatDateOrTimestamp, Localization::SHORT, ($hasMinutes ? Localization::SHORT : Localization::NONE), true);
        if ($hasMinutes) {
            return date('Y-m-d', $tstp);
        } else {
            return date('Y-m-d H:i:s', $tstp);
        }
    }

    /**
     * Begin a transaction.
     *
     * @return void
     */
    protected function _beginTransaction()
    {
        $this->_connect();
        $this->_connection->autocommit(false);
    }

    /**
     * Commit a transaction.
     *
     * @return void
     */
    protected function _commit()
    {
        $this->_connect();
        $this->_connection->commit();
        $this->_connection->autocommit(true);
    }

    /**
     * Roll-back a transaction.
     *
     * @return void
     */
    protected function _rollBack()
    {
        $this->_connect();
        $this->_connection->rollback();
        $this->_connection->autocommit(true);
    }
}
