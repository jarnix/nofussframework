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
        /*
        try {
            $value = $env->$key;
            return $value;
        }
        catch(\Exception $e) {
            try {
                $value = $config->$key;
                return $value;
            }
            catch(\Exception $e) {
                // throw new \Exception('Key "' . $key . '" does not exist');
            }
        }
        */
        return false;
    }
    
    public function __get($key) {
        self::get($key);
    }
        
}