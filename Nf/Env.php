<?php
namespace Nf;

abstract class Env
{

    // reads the .env file, merges the environment on top of the optional .env file, returns the merged "config"
    public static function init() {
        $envFilename = Registry::get('applicationPath') . '/.env';
        if(file_exists($envFilename)) {
            $env = Ini::parse($envFilename, true, Registry::get('locale') . '-' . Registry::get('environment') . '-' . Registry::get('version'), 'common', false);
            $env = self::mergeEnvVariables($env);
        }
        else {
            $env = new \StdClass();
        }
        return Ini::bindArrayToObject($env);
    }
    
    // merges the values from the .env file and the environment variables (they overwrite the previous one)
    private static function mergeEnvVariables($value, $previous = '') {
        $out = [];
        if(is_array($value)) {
            foreach($value as $k => $v) {
                $affect = self::mergeEnvVariables($v, ($previous == '') ? $k : $previous . '.' . $k);
                $out[$k] = $affect;
            }
        }
        else {
            $valueFromEnv = getenv($previous);
            if($valueFromEnv===false) { 
                return $value;
            }
            else {
                return $valueFromEnv;
            }
        }
        return $out;
    }

    /* 
    Gets the value in this order (the last variable overwrites the previous one)
     - .env (at the root directory of the application)
     - server's environment (like SetEnv in .htaccess or SetEnv in apache's virtual host config)
    */
    public static function get($key) {       
        $value = getenv($key);
        if($value===null) {
            return; 
        }
        else {
            return value;
        }
    }
    
}