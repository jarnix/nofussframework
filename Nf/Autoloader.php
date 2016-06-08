<?php

/**
 * Autoloader is a class loader.
 *
 *     <code>
 *      require($library_path . '/php/classes/Nf/Autoloader.php');
 *      $autoloader=new \Nf\Autoloader();
 *      $autoloader->addMap($applicationPath . '/configs/map.php');
 *      $autoloader->addNamespaceRoot('Nf', $libraryPath . '/Nf');
 *      $autoloader->register();
 *     </code>
 *
 * @package Nf
 * @author Julien Ricard
 * @version 1.0
 **/
namespace Nf;

class Autoloader
{

    protected static $_directories = array();

    protected static $_maps = array();

    protected static $_namespaceSeparator = '\\';

    const defaultSuffix = '.php';

    public function __construct()
    {
        
    }
    
    public static function load($className)
    {
        if (! class_exists($className)) {
            $foundInMaps = false;
            
            if (count(self::$_maps) != 0) {
                // reads every map for getting class path
                foreach (self::$_maps as $map) {
                    if (isset($map[$className])) {
                        if (self::includeClass($map[$className], $className)) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
                $foundInMaps = false;
            }
            if (! $foundInMaps) {
                $namespaceRoot = '';
                $fileNamePrefix = '';
                
                // reads each directory until it finds the class file
                if (false !== ($lastNsPos = strripos($className, self::$_namespaceSeparator))) {
                    $namespace = substr($className, 0, $lastNsPos);
                    $namespaceRoot = substr($className, 0, strpos($className, self::$_namespaceSeparator));
                    $shortClassName = substr($className, $lastNsPos + 1);
                    $fileNamePrefix = str_replace(self::$_namespaceSeparator, DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
                } else {
                    $shortClassName = $className;
                }
                
                $fileNamePrefix .= str_replace('_', DIRECTORY_SEPARATOR, $shortClassName);
                
                foreach (self::$_directories as $directory) {
                    if ($directory['namespaceRoot'] == $namespaceRoot && $directory['namespaceRoot'] != '') {
                        // use the specified directory with remaining path
                        $fileNamePrefix = str_replace($namespaceRoot . DIRECTORY_SEPARATOR, '', $fileNamePrefix);
                        if (self::includeClass($directory['path'] . $fileNamePrefix . $directory['suffix'], $className)) {
                            return true;
                        } else {
                            // file was not found in the specified directory
                            return false;
                        }
                    } elseif ($directory['namespaceRoot'] == '') {
                        if (self::includeClass($directory['path'] . $fileNamePrefix . $directory['suffix'], $className)) {
                            return true;
                        }
                    }
                }
            }
        } else {
            return true;
        }
        return false;
    }

    public static function includeClass($file, $class_name)
    {
        if (! class_exists($class_name)) {
            if (file_exists($file)) {
                require_once $file;
                return true;
            } else {
                return false;
            }
        } else {
            // class already exists
        }
    }

    public static function addNamespaceRoot($namespaceRoot, $path, $suffix = self::defaultSuffix)
    {
        if (substr($path, - 1) != DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }
        self::$_directories[] = array(
            'namespaceRoot' => $namespaceRoot,
            'path' => $path,
            'suffix' => $suffix
        );
    }

    public function addMap($mapFilePath = null)
    {
        global $applicationPath;
        global $libraryPath;
        
        if ($mapFilePath === null) {
            $mapFilePath = $applicationPath . '/cache/autoloader.map.php';
        }
        
        if (file_exists($mapFilePath)) {
            if (pathinfo($mapFilePath, PATHINFO_EXTENSION) == 'php') {
                $newMap = require($mapFilePath);
                self::$_maps[] = $newMap;
            }
        }
    }

    public function register()
    {
        spl_autoload_register(__NAMESPACE__ . '\Autoloader::load');
    }
}
