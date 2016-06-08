<?php
namespace Nf;

class Config extends Singleton
{
    use Helper\StoreTrait;
    
    protected static $_instance=null;
    protected static $data;
    
    public static function init($locale, $environment, $version)
    {
        self::$data = new \StdClass();
        $config = Ini::parse(Registry::get('applicationPath') . '/configs/config.ini', true, $locale . '-' . $environment . '-' . $version, 'common', true);
        self::$data = $config;
    }
  
    public function __get($key)
    {
        /*
        because of a bug in php 5.6, we have
        to try an isset before a get within an isset (!!!)
        */
        if ($this->__isset($key)) {
            return $this->magicGet($key);
        } else {
            return false;
        }
    }
    
    public function __debugInfo()
    {
        return [];
    }
}
