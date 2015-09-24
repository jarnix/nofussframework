<?php
namespace Nf;

abstract class Session extends Singleton
{

    protected static $_instance = null;

    public static function factory($namespace, $class, $params, $lifetime)
    {
        $className = '\\' . $namespace . '\\' . ucfirst($class);
        return new $className($params, $lifetime);
    }

    public static function start()
    {
        $config = Registry::get('config');
        if (isset($config->session)) {
            // optional parameters sent to the constructor
            if (isset($config->session->params)) {
                $sessionParams = $config->session->params;
            }
            if (is_object($config->session->handler)) {
                $sessionHandler = self::factory($config->session->handler->namespace, $config->session->handler->class, $sessionParams, $config->session->lifetime);
            } else {
                $sessionHandler = self::factory('Nf\Session', $config->session->handler, $sessionParams, $config->session->lifetime);
            }
            
            session_name($config->session->cookie->name);
            session_set_cookie_params(0, $config->session->cookie->path, $config->session->cookie->domain, false, true);
            
            session_set_save_handler(array(
                &$sessionHandler,
                'open'
            ), array(
                &$sessionHandler,
                'close'
            ), array(
                &$sessionHandler,
                'read'
            ), array(
                &$sessionHandler,
                'write'
            ), array(
                &$sessionHandler,
                'destroy'
            ), array(
                &$sessionHandler,
                'gc'
            ));
            register_shutdown_function('session_write_close');
            session_start();
            // session_regenerate_id(true);
            Registry::set('session', $sessionHandler);
            return $sessionHandler;
        } else {
            return false;
        }
        
    }

    public static function getData()
    {
        return $_SESSION;
    }

    public function __get($key)
    {
        return self::get($key);
    }

    public function __set($key, $value)
    {
        return self::set($key, $value);
    }

    public static function get($key)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        } else {
            return null;
        }
    }

    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function delete($key)
    {
        unset($_SESSION[$key]);
    }
}
