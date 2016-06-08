<?php
namespace Nf;

class Make
{

    /**
     *
     * @param string $action
     *            Executes a make action, like "compress" for putting every file into a single .php file or "map" all the classes in a classmap
     *            You can use your method with make if you call something like:
     *            php index.php -m "\App\Mymake\Compressor::compress?type=js", type=js will be accessible with a
     *            $front = \Nf\Front::getInstance();
     *            $params = $front->getRequest()->getParams();
     *
     */
    
    // merge all the framework files to a single php file, merge all the routes to /cache/allroutes.php
    public static function compress($action = '')
    {
        // merge framework files
        $destFile = Registry::get('applicationPath') . '/cache/nf.all.php';
        if (is_file($destFile)) {
            unlink($destFile);
        }
        // get the actual folder of Nf in the app's settings
        $includedFiles = get_included_files();
        $folder = null;
        
        foreach ($includedFiles as $includedFile) {
            if (preg_match('%Nf\/Autoloader\.php$%', $includedFile, $regs)) {
                $folder = str_replace('/Autoloader.php', '', $includedFile);
                $allFiles = self::getAllFiles($folder);
                // sort by depth for include
                uasort($allFiles, array(
                    'self',
                    'orderFilesByDepth'
                ));
                $bigInclude = '<?' . 'php' . "\n";
                foreach ($allFiles as $file) {
                    if (substr($file, - 4) == '.php') {
                        $bigInclude .= "\n" . str_replace('<?' . 'php', '', file_get_contents($file));
                    }
                }
                file_put_contents($destFile, $bigInclude);
                
                // merge routes files
                $destRoutesFile = Registry::get('applicationPath') . '/cache/routes.all.php';
                if (is_file($destRoutesFile)) {
                    unlink($destRoutesFile);
                }
                $router = \Nf\Router::getInstance();
                $router->setRootRoutes();
                $router->setRoutesFromFiles();
                $router->addAllRoutes();
                $allRoutes = $router->getAllRoutes();
                $bigInclude = '<?' . 'php' . "\n return ";
                $bigInclude .= var_export($allRoutes, true);
                $bigInclude .= ";";
                file_put_contents($destRoutesFile, $bigInclude);
                break;
            }
        }
        
        if ($folder === null) {
            die('The cache already exists, remove the generated files before in /cache (nf.all.php and routes.all.php)' . PHP_EOL);
        }
    }

    private static function getAllFiles($folder)
    {
        $folder = rtrim($folder, '/');
        $root = scandir($folder);
        foreach ($root as $value) {
            if ($value === '.' || $value === '..') {
                continue;
            }
            if (is_file($folder . '/' . $value)) {
                $result[] = $folder . '/' . $value;
                continue;
            }
            foreach (self::getAllFiles($folder . '/' . $value) as $value) {
                $result[] = $value;
            }
        }
        return $result;
    }

    private static function orderFilesByDepth($file1, $file2)
    {
        $t = (substr_count($file1, '/') > substr_count($file2, '/'));
        return $t ? 1 : - 1;
    }
}
