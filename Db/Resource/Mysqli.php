<?php
namespace Nf\Db\Resource;

class Mysqli
{

    private $_sql;

    private $_res;

    private $_adapter;

    public function __construct($sql, $adapter)
    {
        $this->_sql = $sql;
        $this->_adapter = $adapter;
    }

    public function execute()
    {
        $this->_res = $this->_adapter->getConnection()->query($this->_sql);
        if ($this->_res === false) {
            throw new \Exception('The query you tried to execute raised an exception: ' . $this->_sql . ' - ' . $this->_adapter->getConnection()->error);
        }
    }

    public function fetch($mode = null)
    {
        if (! $this->_res) {
            return false;
        }
        
        switch ($mode) {
            case \Nf\Db::FETCH_NUM:
                return $this->_res->fetch_row();
                break;
            case \Nf\Db::FETCH_OBJ:
                return $this->_res->fetch_object();
                break;
            default:
                return $this->_res->fetch_assoc();
        }
    }

    public function fetchAll()
    {
        $data = array();
        while ($row = $this->fetch()) {
            $data[] = $row;
        }
        return $data;
    }

    public function fetchColumn($col = 0)
    {
        $data = array();
        $col = (int) $col;
        $row = $this->fetch(\Nf\Db::FETCH_NUM);
        if (! is_array($row)) {
            return false;
        }
        return $row[$col];
    }

    public function rowCount()
    {
        if (! $this->_adapter) {
            return false;
        }
        $mysqli = $this->_adapter->getConnection();
        return $mysqli->affected_rows;
    }
}
