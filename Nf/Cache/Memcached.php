<?php

namespace Nf\Cache;

use \Nf\Cache;

class Memcached implements CacheInterface
{

    private $_memcache;

    function __construct($params)
    {
        $this->_memcache = new \Memcache;
        if(!isset($params->hosts)) {
            throw new \Exception("No host was set in the settings for the Memcached connector");
        }
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

    public function load($keyName, $keyValues = array())
    {
        if (Cache::isCacheEnabled()) {
            return $this->_memcache->get(Cache::getKeyName($keyName, $keyValues));
        } else {
            return false;
        }
    }

    public function save($keyName, $keyValues, $data, $lifetime = Cache::DEFAULT_LIFETIME)
    {
        if (Cache::isCacheEnabled()) {
            $result = $this->_memcache->set(Cache::getKeyName($keyName, $keyValues), $data, false, $lifetime);
            return $result;
        } else {
            return true;
        }
    }

    public function delete($keyName, $keyValues)
    {
        if (Cache::isCacheEnabled()) {
            $this->_memcache->delete(Cache::getKeyName($keyName, $keyValues), 0);
            return true;
        } else {
            return true;
        }
    }
}
