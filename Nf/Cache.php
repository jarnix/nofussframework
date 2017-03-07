<?php
namespace Nf;

abstract class Cache
{

    static $instances = array();
    
    // default lifetime for any stored value
    const DEFAULT_LIFETIME = 600;

    public static function getKeyName($keyName, $keyValues = array())
    {
        $config = Registry::get('config');
        if (! isset($config->cachekeys->$keyName)) {
            throw new \Exception('Key ' . $keyName . ' is not set in the config file.');
        } else {
            $configKey = $config->cachekeys->$keyName;
            if (is_array($keyValues)) {
                // if we send an associative array
                if (self::isAssoc($keyValues)) {
                    $result = $configKey;
                    foreach ($keyValues as $k => $v) {
                        $result = str_replace('[' . $k . ']', $v, $result);
                    }
                } else {
                    // if we send an indexed array
                    preg_match_all('/\[([^\]]*)\]/', $configKey, $vars, PREG_PATTERN_ORDER);
                    if (count($vars[0]) != count($keyValues)) {
                        throw new \Exception('Key ' . $keyName . ' contains a different number of values than the keyValues you gave.');
                    } else {
                        $result = $configKey;
                        for ($i = 0; $i < count($vars[0]); $i ++) {
                            $result = str_replace('[' . $vars[0][$i] . ']', $keyValues[$i]);
                        }
                    }
                }
            } else {
                // if we send only one value
                $result = preg_replace('/\[([^\]]*)\]/', $keyValues, $configKey);
            }
        }
        // if we still have [ in the key name, it means that we did not send the right parameters for keyValues
        if (strpos($result, '[')) {
            throw new \Exception('The cache key ' . $keyName . ' cannot be parsed with the given keyValues.');
        } else {
            $keyPrefix = ! empty($config->cache->keyPrefix) ? $config->cache->keyPrefix : '';
            return $keyPrefix . $result;
        }
    }

    public static function isCacheEnabled()
    {
        $config = Registry::get('config');
        return isset($config->cache->enabled) ? (bool) $config->cache->enabled : true;
    }

    private static function isAssoc($array)
    {
        return is_array($array) && array_diff_key($array, array_keys(array_keys($array)));
    }
    
    public static function getStorage($type)
    {
        if (! in_array($type, self::$instances)) {
            $config = Registry::get('config');
            if (isset($config->cache->$type->handler)) {
                $handler = $config->cache->$type->handler;
            } else {
                throw new \Exception('The ' . $type . ' cache storage is not defined in the config file');
            }
            if (isset($config->cache->$type->params)) {
                $params = $config->cache->$type->params;
            } else {
                $params = null;
            }
            if (isset($config->cache->$type->lifetime)) {
                $lifetime = $config->cache->$type->lifetime;
            } else {
                $lifetime = self::DEFAULT_LIFETIME;
            }
            if (empty($handler)) {
                $handler = "null";
            }
            $instance = self::factory($handler, $params, $lifetime);
            self::$instances[$type]=$instance;
        }
        return self::$instances[$type];
    }

    public static function factory($handler, $params, $lifetime = DEFAULT_LIFETIME)
    {
        $className = get_class() . '\\' . ucfirst($handler);
        return new $className($params, $lifetime);
    }
}
