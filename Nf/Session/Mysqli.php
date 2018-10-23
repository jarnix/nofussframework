<?php

namespace Nf\Session;

use Nf\Session;
use Nf\Db;
use Nf\Date;

class Mysqli extends Session
{
    protected static $_instance=null;

    private $_lifeTime;
    private $_connection;
    private $_params;

    function __construct($params, $lifetime)
    {
        register_shutdown_function('session_write_close');
        $db = Db::getConnection($params->db_adapter);
        $this->_params=$params;
        $this->_connection=$db;
        $this->_lifeTime = $lifetime;
    }

    function open($savePath, $sessionName)
    {
        return true;
    }

    function close()
    {
        $this->_connection->closeConnection();
        return true;
    }

    function read($sessionId)
    {
        if ($sessionId !== '') {
            $sql="SELECT data FROM " . $this->_params->db_table . " WHERE id=" . $this->_connection->quote($sessionId) . " LIMIT 1";
            $res=$this->_connection->query($sql);
            if ($res->rowCount()>0) {
                $row=$res->fetch();
                return $row['data'];
            }
            else {
                return '';
            }
        }
    }

    function write($sessionId, $data)
    {
        // This is called upon script termination or when session_write_close() is called, which ever is first.
        $values=array(
            'data' => $data,
            'id' => $sessionId,
            'modified' => date('Y-m-d H:i:s'),
            'lifetime' => $this->_lifeTime
        );
        $sql="INSERT INTO " . $this->_params->db_table . " (id, data, modified, lifetime) VALUES(" . $this->_connection->quote($values['id']) . ", " . $this->_connection->quote($values['data']) . ", " . $this->_connection->quote($values['modified']) . ", " . $this->_connection->quote($values['lifetime']) . ")
				ON DUPLICATE KEY UPDATE data=" . $this->_connection->quote($values['data']) . ", modified=" . $this->_connection->quote($values['modified']);
        $this->_connection->query($sql);
        return true;
    }

    function destroy($sessionId)
    {
        $sql="DELETE FROM " . $this->_params->db_table . " WHERE id=" . $sessionId;
        $this->_connection->query($sql);
        return true;
    }

    function gc()
    {
        $sql="DELETE FROM " . $this->_params->db_table . " WHERE modified < DATE_SUB('" . date('Y-m-d H:i:s') . "',INTERVAL lifetime SECOND)";
        $this->_connection->query($sql);
        return true;
    }
}
