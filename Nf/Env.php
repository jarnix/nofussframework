<?php
namespace Nf;

class Env extends Singleton
{
    use Helper\StoreTrait;
    
    protected static $_instance=null;
    private static $data;

    // reads the .env file, merges the environment on top of the optional .env file, returns the merged "config"
    public static function init($locale, $environment, $version)
    {
        $envFilename = Registry::get('applicationPath') . '/.env';
        if (file_exists($envFilename)) {
            $env = Ini::parse($envFilename, true, $locale . '-' . $environment . '-' . $version, 'common', false);
            $env = self::mergeEnvVariables($env);
        } else {
            $env = new \StdClass();
        }
        self::$data = Ini::bindArrayToObject($env);
    }
    
    // merges the values from the .env file and the environment variables (they overwrite the previous one)
    private static function mergeEnvVariables($value, $previous = '')
    {
        $out = [];
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $affect = self::mergeEnvVariables($v, ($previous == '') ? $k : $previous . '.' . $k);
                $out[$k] = $affect;
            }
        } else {
            $valueFromEnv = getenv($previous);
            if ($valueFromEnv===false) {
                return $value;
            } else {
                return $valueFromEnv;
            }
        }
        return $out;
    }

    /*
    Gets the value in this order (the last variable overwrites the previous one)
     - .env (at the root directory of the application)
     - server's environment (like SetEnv in .htaccess or SetEnv in apache's virtual host config)
    This function will return the value of a variable that would not have been defined earlier
    */
    public function __get($key)
    {
        $value = getenv($key);
        // look for the value in environment (that always overwrites the .env value)
        if ($value!==false) {
             return $value;
        } else {
            return $this->magicGet($key);
        }
    }
    
    public function __debugInfo()
    {
        return [];
    }
}
