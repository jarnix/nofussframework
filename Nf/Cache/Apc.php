<?php

namespace Nf\Cache;

use \Nf\Cache;

class Apc implements CacheInterface
{

    function __construct($params)
    {

    }

    public function load($keyName, $keyValues = array())
    {
        if (Cache::isCacheEnabled()) {
            return apc_fetch(Cache::getKeyName($keyName, $keyValues));
        } else {
            return false;
        }
    }

    public function save($keyName, $keyValues, $data, $lifetime = Cache::DEFAULT_LIFETIME)
    {
        if (Cache::isCacheEnabled()) {
            return apc_store(Cache::getKeyName($keyName, $keyValues), $data, $lifetime);
        } else {
            return true;
        }
    }

    public function delete($keyName, $keyValues)
    {
        if (Cache::isCacheEnabled()) {
            return apc_delete(Cache::getKeyName($keyName, $keyValues));
        } else {
            return true;
        }
    }
}
