<?php
namespace Nf;

use Nf\Env;
use Nf\Config;

class Settings extends Singleton
{
      
    protected static $_instance=null;
      
    public static function get($key) {
        $env = Env::getInstance();
        $config = Config::getInstance();
        
        if(isset($env->$key)) {
            $value = $env->$key;
        }
        else {
             if(isset($config->$key)) {
                 $value = $config->$key;
             }
        }
        return $value;
        return false;
    }
    
    public function __get($key) {
        self::get($key);
    }
        
}