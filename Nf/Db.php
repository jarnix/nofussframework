<?php
namespace Nf;

class Db
{

    const FETCH_ASSOC = 2;

    const FETCH_NUM = 3;

    const FETCH_OBJ = 5;

    const FETCH_COLUMN = 7;

    private static $_connections = array();

    public static $_forceStoreConnectionInInstance = null;

    public static function factory($config)
    {
        if (! is_array($config)) {
            // convert to an array
            $conf = array();
            $conf['adapter'] = $config->adapter;
            $conf['params'] = (array) $config->params;
            $conf['profiler'] = (array) $config->profiler;
        } else {
            $conf = $config;
        }
        $adapterName = get_class() . '\\Adapter\\' . $conf['adapter'];
        $dbAdapter = new $adapterName($conf['params']);
        $dbAdapter->setProfilerConfig($conf['profiler']);
        return $dbAdapter;
    }

    public static function getConnection($configName, $alternateHostname = null, $alternateDatabase = null, $storeInInstance = true)
    {
        $config = \Nf\Registry::get('config');

        if (!isset($config->db->$configName)) {
            throw new \Exception('The adapter "' . $configName . '" is not defined in the config file');
        }

        if (self::$_forceStoreConnectionInInstance !== null) {
            $storeInInstance = self::$_forceStoreConnectionInInstance;
        }

        $defaultHostname = $config->db->$configName->params->hostname;
        $defaultDatabase = $config->db->$configName->params->database;
        $hostname = ($alternateHostname !== null) ? $alternateHostname : $defaultHostname;
        $database = ($alternateDatabase !== null) ? $alternateDatabase : $defaultDatabase;

        if (isset($config->db->$configName->params->port)) {
            $port = $config->db->$configName->params->port;
        } else {
            $port = null;
        }

        // if the connection has already been created and if we store the connection in memory for future use
        if (isset(self::$_connections[$configName . '-' . $hostname . '-' . $database]) && $storeInInstance) {
            return self::$_connections[$configName . '-' . $hostname . '-' . $database];
        } else {
            // optional profiler config
            $profilerConfig = isset($config->db->$configName->profiler) ? (array)$config->db->$configName->profiler : null;
            if ($profilerConfig != null) {
                $profilerConfig['name'] = $configName;
            }

            // or else we create a new connection
            $dbConfig = array(
                'adapter' => $config->db->$configName->adapter,
                'params' => array(
                    'hostname' => $hostname,
                    'port'     => $port,
                    'username' => $config->db->$configName->params->username,
                    'password' => $config->db->$configName->params->password,
                    'database' => $database,
                    'charset' => $config->db->$configName->params->charset
                ),
                'profiler' => $profilerConfig
            );

            // connection with the factory method
            $dbConnection = self::factory($dbConfig);
            if ($storeInInstance) {
                self::$_connections[$configName . '-' . $hostname . '-' . $database] = $dbConnection;
            }
            return $dbConnection;
        }
    }
}
