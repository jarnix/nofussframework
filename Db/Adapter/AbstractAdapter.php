<?php
namespace Nf\Db\Adapter;

abstract class AbstractAdapter
{

    const PROFILER_NAMESPACE_ROOT = '\Nf\Db\Profiler';
    
    protected $_config = array();

    protected $_connection = null;

    protected $_autoQuoteIdentifiers = true;

    protected $_cache = false;
    
    protected $profiler = false;

    public function __construct($config)
    {
        if (! is_array($config)) {
            throw new \Exception('Adapter parameters must be in an array');
        }
        if (! isset($config['charset'])) {
            $config['charset'] = null;
        }
        $this->_config = $config;
    }

    public function getConnection()
    {
        $this->_connect();
        return $this->_connection;
    }

    public function query($sql)
    {
        $this->_connect();
        $res = new $this->_resourceClass($sql, $this);
        
        $beginTime = microtime(true);
        
        $res->execute();
        
        $endTime = microtime(true);
        
        if ($this->profiler) {
            $this->profiler->afterQuery($res, $sql, $endTime-$beginTime);
        }
        
        return $res;
    }

    public function fetchAll($sql)
    {
        $cacheKey = md5($sql) . 'All';
        
        if (($result = $this->_getCachedResult($cacheKey)) === false) {
            $stmt = $this->query($sql);
            $result = $stmt->fetchAll(\Nf\Db::FETCH_ASSOC);
            $this->_setCachedResult($cacheKey, $result);
        }
        $this->disableCache();
        return $result;
    }

    public function fetchAssoc($sql)
    {
        $cacheKey = md5($sql) . 'Assoc';
        
        if (($result = $this->_getCachedResult($cacheKey)) === false) {
            $stmt = $this->query($sql);
            $result = array();
            while ($row = $stmt->fetch(\Nf\Db::FETCH_ASSOC)) {
                $tmp = array_values(array_slice($row, 0, 1));
                $result[$tmp[0]] = $row;
            }
            $this->_setCachedResult($cacheKey, $result);
        }
        $this->disableCache();
        return $result;
    }

    public function fetchRow($sql)
    {
        $cacheKey = md5($sql) . 'Row';
        
        if (($result = $this->_getCachedResult($cacheKey)) === false) {
            $stmt = $this->query($sql);
            $result = $stmt->fetch();
            $this->_setCachedResult($cacheKey, $result);
        }
        $this->disableCache();
        return $result;
    }

    public function fetchCol($sql)
    {
        $cacheKey = md5($sql) . 'Col';
        
        if (($result = $this->_getCachedResult($cacheKey)) === false) {
            $stmt = $this->query($sql);
            $result = $stmt->fetchAll(\Nf\Db::FETCH_COLUMN, 0);
            $this->_setCachedResult($cacheKey, $result);
        }
        $this->disableCache();
        return $result;
    }

    public function fetchOne($sql)
    {
        $cacheKey = md5($sql) . 'One';
        
        if (($result = $this->_getCachedResult($cacheKey)) === false) {
            $stmt = $this->query($sql);
            $result = $stmt->fetchColumn(0);
            $this->_setCachedResult($cacheKey, $result);
        }
        $this->disableCache();
        return $result;
    }

    public function fetchPairs($sql)
    {
        $cacheKey = md5($sql) . 'Pairs';
        
        if (($result = $this->_getCachedResult($cacheKey)) === false) {
            $stmt = $this->query($sql);
            $result = array();
            while ($row = $stmt->fetch(\Nf\Db::FETCH_NUM)) {
                $result[$row[0]] = $row[1];
            }
            $this->_setCachedResult($cacheKey, $result);
        }
        $this->disableCache();
        return $result;
    }

    public function beginTransaction()
    {
        $this->_beginTransaction();
        return $this;
    }

    public function commit()
    {
        $this->_commit();
        return $this;
    }

    public function rollback()
    {
        $this->_rollback();
        return $this;
    }

    public function enableCache($lifetime = \Nf\Cache::DEFAULT_LIFETIME, $cacheKey = null)
    {
        $this->_cache = array(
            'lifetime' => $lifetime
        );
        if ($cacheKey !== null) {
            $this->_cache['key'] = $cacheKey;
        }
        return $this;
    }

    public function disableCache()
    {
        $this->_cache = false;
        return $this;
    }

    protected function _getCachedResult($cacheKey)
    {
        if ($this->_cache !== false) {
            $cache = \Nf\Front::getInstance()->getCache('global');
            $cacheKey = isset($this->_cache['key']) ? $this->_cache['key'] : $cacheKey;
            return $cache->load('sql', $cacheKey);
        }
        return false;
    }

    protected function _setCachedResult($cacheKey, $result)
    {
        if ($this->_cache !== false) {
            $cache = \Nf\Front::getInstance()->getCache('global');
            $cacheKey = isset($this->_cache['key']) ? $this->_cache['key'] : $cacheKey;
            return $cache->save('sql', $cacheKey, $result, $this->_cache['lifetime']);
        }
        return false;
    }

    protected function _quote($value)
    {
        if (null === $value) {
            return 'NULL';
        } elseif (is_int($value) || $value instanceof \Nf\Db\Expression) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        } else {
            return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";
        }
    }

    public function quote($value, $type = null)
    {
        $this->_connect();
        return $this->_quote($value);
    }

    public function quoteIdentifier($ident, $auto = false)
    {
        return $this->_quoteIdentifierAs($ident, null, $auto);
    }

    public function quoteColumnAs($ident, $alias, $auto = false)
    {
        return $this->_quoteIdentifierAs($ident, $alias, $auto);
    }

    protected function _quoteIdentifierAs($ident, $alias = null, $auto = false, $as = ' AS ')
    {
        if (is_string($ident)) {
            $ident = explode('.', $ident);
        }
        if (is_array($ident)) {
            $segments = array();
            foreach ($ident as $segment) {
                $segments[] = $this->_quoteIdentifier($segment, $auto);
            }
            if ($alias !== null && end($ident) == $alias) {
                $alias = null;
            }
            $quoted = implode('.', $segments);
        } else {
            $quoted = $this->_quoteIdentifier($ident, $auto);
        }
        
        if ($alias !== null) {
            $quoted .= $as . $this->_quoteIdentifier($alias, $auto);
        }
        return $quoted;
    }

    protected function _quoteIdentifier($value, $auto = false)
    {
        if ($auto === false || $this->_autoQuoteIdentifiers === true) {
            $q = $this->getQuoteIdentifierSymbol();
            return ($q . str_replace("$q", "$q$q", $value) . $q);
        }
        return $value;
    }

    public function getQuoteIdentifierSymbol()
    {
        return '"';
    }

    public function setProfilerConfig($profilerConfig)
    {
        if ($profilerConfig!=null) {
            if (isset($profilerConfig['class'])) {
                if (!empty($profilerConfig['class'])) {
                    $profilerClass = $profilerConfig['class'];
                    unset($profilerConfig['class']);
                    $optionalConfig = $profilerConfig;
                    $profilerFullClassName = self::PROFILER_NAMESPACE_ROOT . '\\' . $profilerClass;
                    $profilerInstance = new $profilerFullClassName($optionalConfig);
                    $this->profiler = $profilerInstance;
                }
            } else {
                throw new \Exception('You must set the profiler class name in the config.ini file');
            }
        }
    }
    
    abstract protected function _connect();

    abstract public function isConnected();

    abstract public function closeConnection();

    abstract public function lastInsertId($tableName = null, $primaryKey = null);
}
