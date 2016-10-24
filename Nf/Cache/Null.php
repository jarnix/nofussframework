<?php

namespace Nf\Cache;

use \Nf\Cache;

class Null implements CacheInterface
{

    function __construct($params)
    {

    }

    public function load($keyName, $keyValues = array())
    {
        return false;
    }

    public function save($keyName, $keyValues, $data, $lifetime = Cache::DEFAULT_LIFETIME)
    {
        return true;
    }

    public function delete($keyName, $keyValues)
    {
        return true;
    }
}
