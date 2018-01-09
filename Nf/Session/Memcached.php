<?php

namespace Nf\Session;

use Nf\Session;
use Nf\Cache;

class Memcached extends Session
{
    protected static $_instance=null;

    private $_lifeTime;
    private $_memcache;

    function __construct($params, $lifetime)
    {
        register_shutdown_function('session_write_close');
        $this->_memcache = new \Memcache;
        $this->_lifeTime = $lifetime;
        if (strpos($params->hosts, ',')>0) {
            $hosts=explode(',', $params->hosts);
            foreach ($hosts as $host) {
                $this->_memcache->addServer($host, $params->port);
            }
            unset($host);
        } else {
            $this->_memcache->addServer($params->hosts, $params->port);
        }
    }

    function open($savePath, $sessionName)
    {
        return true;
    }

    function close()
    {
        $this->_memcache = null;
        return true;
    }

    function read($sessionId)
    {
        $sessionId = session_id();
        $cacheKey = Cache::getKeyName('session', $sessionId);
        if ($sessionId !== "") {
            return $this->_memcache->get($cacheKey);
        }
    }

    function write($sessionId, $data)
    {
        // This is called upon script termination or when session_write_close() is called, which ever is first.
        $cacheKey = Cache::getKeyName('session', $sessionId);
        $result = $this->_memcache->set($cacheKey, $data, false, $this->_lifeTime);
        return $result;
    }

    function destroy($sessionId)
    {
        $cacheKey=Cache::getKeyName('session', $sessionId);
        $this->_memcache->delete($cacheKey, 0);
        return true;
    }

    function gc($notUsedInMemcached)
    {
        return true;
    }
}
