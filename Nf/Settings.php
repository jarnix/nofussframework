<?php
namespace Nf;

use Nf\Env;
use Nf\Config;

class Settings extends Singleton
{
      
    protected static $_instance = null;
      
    public static function get($key = null)
    {
        
        if ($key===null) {
            return self::getInstance();
        } else {
            if (strpos($key, '.')) {
                $explodedKey = explode('.', $key);
                $tempValue = self::getInstance();
                foreach ($explodedKey as $k) {
                    $tempValue = $tempValue->$k;
                }
                return $tempValue;
            } else {
                $env = Env::getInstance();
                $config = Config::getInstance();
                
                $value = false;
                
                if (isset($env->$key)) {
                    $value = $env->$key;
                } else {
                    if (isset($config->$key)) {
                        $value = $config->$key;
                    }
                }
                return $value;
            }
        }
    }
    
    public function __get($key)
    {
        return self::get($key);
    }
    
    public function __isset($key)
    {
        $env = Env::getInstance();
        $config = Config::getInstance();
        return (isset($env->$key) || isset($config->$key));
    }
    
    public function __debugInfo()
    {
        return [];
    }
}
