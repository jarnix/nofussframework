<?php
namespace Nf;

use Nf\Registry;
use Nf\Front;

class Router extends Singleton
{
    
    protected static $_instance=null;
    
    // pour le routeur
    private $routingPreferences = array();

    private $routesDirectories = array();

    private $rootRoutesDirectories = array();

    private $allRoutesByVersionAndLocale = array();

    private $activeRoute = array();

    private $allVersionsUrls = array();

    const rootRouteFilename = '_root.php';

    const defaultRequestType = 'default';

    const defaultRouteName = 'default';
    
    // routes
    public function addAllRoutes()
    {
                
        if (Registry::get('environment')!='dev' && file_exists(Registry::get('applicationPath') . '/cache/routes.all.php')) {
            $this->allRoutesByVersionAndLocale = require(Registry::get('applicationPath') . '/cache/routes.all.php');
        } else {
            $routesDirectory = realpath(Registry::get('applicationPath') . '/routes');
            $directory = new \RecursiveDirectoryIterator($routesDirectory);
            $files = new \RecursiveIteratorIterator($directory);
            $allRouteFiles = array();
            foreach ($files as $file) {
                $pathname = ltrim(str_replace($routesDirectory, '', $file->getPathname()), '/');
                // if it's not a folder or anything other than a .php
                if (substr($pathname, - 1, 1) != '.' && substr($pathname, -4)=='.php') {
                    $allRouteFiles[] = $pathname;
                }
            }
            // sort allRouteFiles by their depth to allow inheriting a route from all versions and or locales
            usort($allRouteFiles, function ($a, $b) {
                return substr_count($a, '/') > substr_count($b, '/');
            });
            foreach ($allRouteFiles as $file) {
                $pathname = ltrim(str_replace($routesDirectory, '', $file), '/');
            
                $arrPath = explode('/', $pathname);
            
                // routes are sorted by version and locale
                if (count($arrPath) == 3) {
                    $version = $arrPath[0];
                    $locale = $arrPath[1];
                } elseif (count($arrPath) == 2) {
                    $version = $arrPath[0];
                    $locale = '*';
                } elseif (count($arrPath) == 1) {
                    $version = '*';
                    $locale = '*';
                }
                // add the route to allRoutes, sorted by version and locale
                // all your routes are belong to us
                if (! isset($this->allRoutesByVersionAndLocale[$version])) {
                    $this->allRoutesByVersionAndLocale[$version] = array();
                }
                if (! isset($this->allRoutesByVersionAndLocale[$version][$locale])) {
                    $this->allRoutesByVersionAndLocale[$version][$locale] = array();
                }
                if (basename($file) != self::rootRouteFilename) {
                    $subPath = str_replace('.php', '', basename($file));
                } else {
                    $subPath = '';
                }
                $newRoutes = require $routesDirectory . '/' . $pathname;
                // the file doesn't contain an array, or contains nothing => we ignore it
                if (is_array($newRoutes)) {
                    foreach ($newRoutes as &$newRoute) {
                        if (isset($newRoute['type']) && $newRoute['type']=='inherit') {
                            // go up one level until we find the route to inherit from
                            if (isset($this->allRoutesByVersionAndLocale[$version]['*'][$newRoute['from']])) {
                                $routeToAdd = $this->allRoutesByVersionAndLocale[$version]['*'][$newRoute['from']];
                                $routeToAdd['regexp'] = $routeToAdd['inheritableRegexp'];
                                $routeToAdd['regexp'] = ltrim($routeToAdd['regexp'], '/');
                                $routeToAdd['regexp'] = rtrim(ltrim($subPath . '/' . $routeToAdd['regexp'], '/'), '/');
                            } elseif (isset($this->allRoutesByVersionAndLocale['*']['*'][$newRoute['from']])) {
                                $routeToAdd = $this->allRoutesByVersionAndLocale['*']['*'][$newRoute['from']];
                                $routeToAdd['regexp'] = $routeToAdd['inheritableRegexp'];
                                $routeToAdd['regexp'] = ltrim($routeToAdd['regexp'], '/');
                                $routeToAdd['regexp'] = rtrim(ltrim($subPath . '/' . $routeToAdd['regexp'], '/'), '/');
                            }
                            $this->allRoutesByVersionAndLocale[$version][$locale][$routeToAdd['name']] = $routeToAdd;
                        } else {
                            if (isset($newRoute['regexp'])) {
                                $newRoute['regexp'] = ltrim($newRoute['regexp'], '/');
                                $newRoute['inheritableRegexp'] = $newRoute['regexp'];
                                // special case for $ for answering to /something$ (setting something$ and not /something/$ as the regexp)
                                if($newRoute['regexp'] == '$') {
                                    $newRoute['regexp'] = rtrim(ltrim($subPath . $newRoute['regexp'], '/'), '/');
                                }
                                else {
                                    $newRoute['regexp'] = rtrim(ltrim($subPath . '/' . $newRoute['regexp'], '/'), '/');
                                }
                            }
                            if (isset($newRoute['name'])) {
                                $this->allRoutesByVersionAndLocale[$version][$locale][$newRoute['name']] = $newRoute;
                            } else {
                                $this->allRoutesByVersionAndLocale[$version][$locale][] = $newRoute;
                            }
                        }
                    }
                }
            }
        }
    }

    public function setRoutesFromFiles()
    {
        $this->routingPreferences[] = 'files';
    }

    public function setStructuredRoutes()
    {
        $this->routingPreferences[] = 'structured';
    }

    public function setRootRoutes()
    {
        $this->routingPreferences[] = 'root';
    }

    public function findRoute($version, $locale)
    {
        $foundController = null;
        $config = Registry::get('config');
        $front = Front::getInstance();
        $originalUri = $front->getRequest()->getUri();
        
        // remove everything after a '?' which is not used in the routing system
        $uri = preg_replace('/\?.*$/', '', $originalUri);
        
        // strip the trailing slash, also unused
        $uri = rtrim((string) $uri, '/');
        
        foreach ($this->routingPreferences as $routingPref) {
            if ($routingPref == 'files') {
                $foundController = $this->findRouteFromFiles($uri, $version, $locale);
                // search by version only
                if (!$foundController) {
                    $foundController = $this->findRouteFromFiles($uri, $version, '*');
                }
                // search without version nor locale
                if (!$foundController) {
                    $foundController = $this->findRouteFromFiles($uri, '*', '*');
                }
            }
            
            if (! $foundController && $routingPref == 'structured') {
                // l'url doit être de la forme /m/c/a/, ou /m/c/ ou /m/
                if (preg_match('#^(\w+)/?(\w*)/?(\w*)#', $uri, $uriSegments)) {
                    $uriSegments[2] = ! empty($uriSegments[2]) ? $uriSegments[2] : 'index';
                    $uriSegments[3] = ! empty($uriSegments[3]) ? $uriSegments[3] : 'index';
                    
                    // on regarde si on a un fichier et une action pour le même chemin dans les répertoires des modules
                    if ($foundController = $front->checkModuleControllerAction($uriSegments[1], $uriSegments[2], $uriSegments[3])) {
                        $this->activeRoute = array(
                            'type' => self::defaultRequestType,
                            'name' => self::defaultRouteName,
                            'components' => array()
                        );
                        
                        // les éventuels paramètres sont en /variable/value
                        $paramsFromUri = ltrim(preg_replace('#^(\w+)/(\w+)/(\w+)#', '', $uri), '/');
                        
                        // si on envoie des variables avec des /
                        if ($paramsFromUri != '') {
                            if (substr_count($paramsFromUri, '/') % 2 == 1) {
                                preg_match_all('/([\w_]+)\/([^\/]*)/', $paramsFromUri, $arrParams, PREG_SET_ORDER);
                                for ($matchi = 0; $matchi < count($arrParams); $matchi ++) {
                                    $front->getRequest()->setParam($arrParams[$matchi][1], $arrParams[$matchi][2]);
                                }
                            }
                            
                            // si on envoie des variables avec des var1=val1
                            if (substr_count($paramsFromUri, '=') >= 1) {
                                preg_match_all('/([\w_]+)=([^\/&]*)/', $paramsFromUri, $arrParams, PREG_SET_ORDER);
                                for ($matchi = 0; $matchi < count($arrParams); $matchi ++) {
                                    $front->getRequest()->setParam($arrParams[$matchi][1], $arrParams[$matchi][2]);
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // si c'est la route par défaut
        if (! $foundController) {
            if (empty($uri)) {
                if ($foundController = $front->checkModuleControllerAction($config->front->default->module, $config->front->default->controller, $config->front->default->action)) {
                    if (isset($route[2]) && isset($result[1])) {
                        $front->associateParams($route[2], $result[1]);
                    }
                }
            }
        }
        
        // reads which middlewares are required and adds them
        if ($foundController) {
            if (isset($this->activeRoute['middlewares'])) {
                $this->activeRoute['middlewaresPre'] = array();
                $this->activeRoute['middlewaresPost'] = array();
                foreach ($this->activeRoute['middlewares'] as $middlewareClass) {
                    if (! class_exists($middlewareClass)) {
                        throw new \Exception('The middleware ' . $middlewareClass . ' cannot be found. Matched route: ' . print_r($this->activeRoute, true));
                    }
                    if (isset(class_uses($middlewareClass)['Nf\Middleware\Pre'])) {
                        $this->activeRoute['middlewaresPre'][] = $middlewareClass;
                    } else {
                        $this->activeRoute['middlewaresPost'][] = $middlewareClass;
                    }
                }
            }
        }
        
        return $foundController;
    }
    
    private function findRouteFromFiles($uri, $version, $locale)
    {
        
        $foundController = null;
        $front = Front::getInstance();
        
        if (isset($this->allRoutesByVersionAndLocale[$version][$locale])) {
            $routes = $this->allRoutesByVersionAndLocale[$version][$locale];
        
            if (! $foundController) {
                $routes = array_reverse($routes);
        
                foreach ($routes as $route) {
                    if (! $foundController) {
                        // default type is "default"
                        $requestType = 'default';
        
                        // if a specific type is requested
                        if (isset($route['type'])) {
                            $requestType = $route['type'];
                        }
        
                        $routeRegexpWithoutNamedParams = preg_replace('/\([\w_]+:/', '(', $route['regexp']);
        
                        $arrRouteModuleControllerAction = explode('/', $route['controller']);
          
                        // check if this is a match, or else continue until we have a match
                        if (preg_match('#^' . $routeRegexpWithoutNamedParams . '#', $uri, $refs)) {
                            // if using a rest request, the user can override the method
                            if ($requestType == 'rest') {
                                // default action
                                if (isset($_SERVER['REQUEST_METHOD'])) {
                                    $action = strtolower($_SERVER['REQUEST_METHOD']);
                                }
                                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                                    // overloading the method with the "method" parameter if the request is POST
                                    if (isset($_POST['method'])) {
                                        $action = strtolower($_POST['method']);
                                    }
                                    // overloading the method with http headers
                                    // X-HTTP-Method (Microsoft) or X-HTTP-Method-Override (Google/GData) or X-METHOD-OVERRIDE (IBM)
                                    $acceptableOverridingHeaders = array(
                                        'HTTP_X_HTTP_METHOD',
                                        'HTTP_X_HTTP_METHOD_OVERRIDE',
                                        'HTTP_X_METHOD_OVERRIDE'
                                    );
                                    foreach ($acceptableOverridingHeaders as $overridingHeader) {
                                        if (isset($_SERVER[$overridingHeader])) {
                                            $action = strtolower($_SERVER[$overridingHeader]);
                                        }
                                    }
                                }
                                                                
                                // if overriding the action in the route
                                if (isset($arrRouteModuleControllerAction[2])) {
                                    $action = $arrRouteModuleControllerAction[2];
                                }
                            } else {
                                $action = $arrRouteModuleControllerAction[2];
                            }
                            
                            // on teste la présence du module controller action indiqué dans la route
                            if ($foundController = $front->checkModuleControllerAction($arrRouteModuleControllerAction[0], $arrRouteModuleControllerAction[1], $action)) {
                                $this->activeRoute = $route;
                                $front->setRequestType($requestType);
                                $front->associateParams($route['regexp'], $refs);
                                break;
                            }
                        }
                    }
                }
                unset($route);
            }
        }
        return $foundController;
    }

    public function getAllRoutes()
    {
        return $this->allRoutesByVersionAndLocale;
    }
    
    public function getActiveRoute()
    {
        return $this->activeRoute;
    }

    // returns the url from the defined routes by its name
    public function getNamedUrl($name, $params = array(), $version = null, $locale = null)
    {
        if ($version == null) {
            $version = Registry::get('version');
        }
        if ($locale == null) {
            $locale = Registry::get('locale');
        }
        $foundRoute = false;
        if (isset($this->allRoutesByVersionAndLocale[$version][$locale][$name])) {
            $url = $this->allRoutesByVersionAndLocale[$version][$locale][$name]['regexp'];
            $foundRoute = true;
        } elseif (isset($this->allRoutesByVersionAndLocale[$version]['*'][$name])) {
            $url = $this->allRoutesByVersionAndLocale[$version]['*'][$name]['regexp'];
            $foundRoute = true;
        } elseif (isset($this->allRoutesByVersionAndLocale['*']['*'][$name])) {
            $url = $this->allRoutesByVersionAndLocale['*']['*'][$name]['regexp'];
            $foundRoute = true;
        }
        if ($foundRoute) {
            preg_match_all('/\(([\w_]+):([^)]+)\)/im', $url, $result, PREG_SET_ORDER);
            for ($matchi = 0; $matchi < count($result); $matchi ++) {
                if (isset($params[$result[$matchi][1]])) {
                    $url = str_replace($result[$matchi][0], $params[$result[$matchi][1]], $url);
                }
            }
            return $url;
        } else {
            throw new \Exception('Cannot find route named "' . $name . '" (version=' . $version . ', locale=' . $locale . ')');
        }
    }
}
