<?php
namespace Nf;

class Front extends Singleton
{

    protected static $_instance;
    
    // les modules
    private $_moduleDirectories = array();

    const controllersDirectory = 'controllers';
    
    // pour instancier le controller, forwarder...
    private $_moduleNamespace;

    private $_moduleName;

    private $_controllerName;

    private $_actionName;
    
    // pour le controller
    private $_request;

    private $_requestType;

    private $_response;

    private $_router;

    private $_session;

    public static $obLevel = 0;
    
    // the instance of the controller that is being dispatched
    private $_controllerInstance;

    private $_applicationNamespace = 'App';

    private $registeredMiddlewares = array();

    const MIDDLEWARE_PRE = 0;

    const MIDDLEWARE_POST = 1;

    public function __get($var)
    {
        $varName = '_' . $var;
        return $this->$varName;
    }

    public function getRequestType()
    {
        return $this->_requestType;
    }

    public function getModuleName()
    {
        return $this->_moduleName;
    }

    public function getControllerName()
    {
        return $this->_controllerName;
    }

    public function getActionName()
    {
        return $this->_actionName;
    }

    public function setRequest($request)
    {
        $this->_request = $request;
    }

    public function setResponse($response)
    {
        $this->_response = $response;
    }

    public function setRequestType($requestType)
    {
        $this->_requestType = $requestType;
    }

    public function getRequest()
    {
        return $this->_request;
    }

    public function getResponse()
    {
        return $this->_response;
    }

    public function setSession($session)
    {
        $this->_session = $session;
    }

    public function getSession()
    {
        return $this->_session;
    }

    public function getRouter()
    {
        return $this->_router;
    }

    public function setRouter($router)
    {
        $this->_router = $router;
    }

    public function setApplicationNamespace($namespace)
    {
        $this->_applicationNamespace = $namespace;
    }

    public function getApplicationNamespace()
    {
        return $this->_applicationNamespace;
    }

    public function getControllerInstance()
    {
        return $this->_controllerInstance;
    }
    
    // cache
    public function getCache($which)
    {
        // do we already have the cache object in the Registry ?
        if (Registry::isRegistered('cache_' . $which)) {
            return Registry::get('cache_' . $which);
        } else {
            // get the config for our cache object
            $config = Registry::get('config');
            if (isset($config->cache->$which->handler)) {
                $cache = Cache::factory($config->cache->$which->handler, (isset($config->cache->$which->params)) ? $config->cache->$which->params : array(), (isset($config->cache->$which->lifetime)) ? $config->cache->$which->lifetime : Cache::DEFAULT_LIFETIME);
                return $cache;
            } else {
                throw new Exception('The cache handler "' . $which . '" is not set in config file');
            }
        }
    }
    
    // modules
    public function addModuleDirectory($namespace, $dir)
    {
        $this->_moduleDirectories[] = array(
            'namespace' => $namespace,
            'directory' => $dir
        );
    }

    private function getControllerFilename($namespace, $directory, $module, $controller)
    {
        $controllerFilename = ucfirst($controller . 'Controller.php');
        return $directory . $module . '/' . self::controllersDirectory . '/' . $controllerFilename;
    }

    public function checkModuleControllerAction($inModule, $inController, $inAction)
    {
        $foundController = null;
        
        foreach ($this->_moduleDirectories as $moduleDirectory => $moduleDirectoryInfos) {
            $controllerFilename = $this->getControllerFilename($moduleDirectoryInfos['namespace'], $moduleDirectoryInfos['directory'], $inModule, $inController);
            
            if (file_exists($controllerFilename)) {
                $this->_moduleNamespace = $moduleDirectoryInfos['namespace'];
                $this->_moduleName = $inModule;
                $this->_controllerName = $inController;
                $this->_actionName = $inAction;
                $foundController = $controllerFilename;
                break;
            }
        }
        
        unset($moduleDirectory);
        unset($moduleDirectoryInfos);
        if (! $foundController) {
            return false;
        }
        return $foundController;
    }

    public function forward($module, $controller, $action)
    {
        if ($foundController = $this->checkModuleControllerAction($module, $controller, $action)) {
            if ($this->checkMethodForAction($foundController)) {
                $this->launchAction();
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function associateParams($routeRegexp, $values)
    {
        if (! is_array($values)) {
            $values = array(
                $values
            );
        }
        
        preg_match_all('/\((\w+):/', $routeRegexp, $matches);
        
        for ($i = 0; $i < count($matches[1]); $i ++) {
            $this->_request->setParam($matches[1][$i], $values[$i + 1]);
        }
    }

    public function parseParameters($uri)
    {
        // les éventuels paramètres sont en /variable/value
        $paramsFromUri = ltrim(preg_replace('#^(\w+)/(\w+)/(\w+)#', '', $uri), '/');
        
        // si on envoie des variables avec des /
        if ($paramsFromUri != '') {
            if (substr_count($paramsFromUri, '/') % 2 == 1) {
                preg_match_all('/(\w+)\/([^\/]*)/', $paramsFromUri, $arrParams, PREG_SET_ORDER);
                for ($matchi = 0; $matchi < count($arrParams); $matchi ++) {
                    $this->_request->setParam($arrParams[$matchi][1], $arrParams[$matchi][2]);
                }
            }
            
            // si on envoie des variables avec des var1=val1
            if (substr_count($paramsFromUri, '=') >= 1) {
                preg_match_all('/(\w+)=([^\/&]*)/', $paramsFromUri, $arrParams, PREG_SET_ORDER);
                for ($matchi = 0; $matchi < count($arrParams); $matchi ++) {
                    $this->_request->setParam($arrParams[$matchi][1], $arrParams[$matchi][2]);
                }
            }
        }
    }

    public function getView()
    {
        if (! is_null($this->_controllerInstance->_view)) {
            return $this->_controllerInstance->_view;
        } else {
            $config = Registry::get('config');
            $view = View::factory($config->view->engine);
            $view->setResponse($this->_response);
            return $view;
        }
    }

    public function dispatch()
    {
        // on va regarder le m/c/a concerné par l'url ou les paramètres déjà saisis
        if ($foundController = $this->getRouter()->findRoute(Registry::get('version'), Registry::get('locale'))) {
            return $this->checkMethodForAction($foundController);
        } else {
            return false;
        }
    }

    private function checkMethodForAction($foundController)
    {
        // on lancera dans l'ordre le init, action
        require_once($foundController);
        $controllerClassName = $this->_moduleNamespace . '\\' . ucfirst($this->_moduleName) . '\\' . ucfirst($this->_controllerName) . 'Controller';
        $this->_controllerInstance = new $controllerClassName($this);
        
        $reflected = new \ReflectionClass($this->_controllerInstance);
        
        if ($reflected->hasMethod($this->_actionName . 'Action')) {
            return true;
        } else {
            return false;
        }
    }
    
    // called after dispatch
    public function init()
    {
        return $this->_controllerInstance->init();
    }
    
    // registers a middleware programmatically and not through a route
    public function registerMiddleware($middlewareInstance)
    {
        if (isset(class_uses($middlewareInstance)['Nf\Middleware\Pre'])) {
            $key = self::MIDDLEWARE_PRE;
        } else {
            $key = self::MIDDLEWARE_POST;
        }
        // adds the middleware
        $this->registeredMiddlewares[$key][] = $middlewareInstance;
    }
    
    // calls the actual action found from the routing system
    public function launchAction()
    {
        self::$obLevel = ob_get_level();
        
        if (php_sapi_name() != 'cli') {
            ob_start();
        }
        
        $router = $this->_router;
        $activeRoute = $router->getActiveRoute();
        
        // optionally sets the content-type
        if (isset($activeRoute['contentType'])) {
            $this->_response->setContentType($activeRoute['contentType']);
        }
        
        // optionally sets the client cache duration
        if (isset($activeRoute['cacheMinutes'])) {
            $this->_response->setCacheable($activeRoute['cacheMinutes']);
        }
        
        // flag to allow the code of the controller to be executed
        // or not if a middleware returns false
        $allowedByPreMiddleware = true;
        
        // call pre middlewares defined by the active route
        if (isset($activeRoute['middlewaresPre'])) {
            foreach ($activeRoute['middlewaresPre'] as $middleware) {
                if ($allowedByPreMiddleware) {
                    $object = new $middleware();
                    $ret = $object->execute();
                    if ($ret === false) {
                        $allowedByPreMiddleware = false;
                        break;
                    }
                }
            }
            unset($middleware);
        }
        // call pre middlewares defined programatically
        if (isset($this->registeredMiddlewares[self::MIDDLEWARE_PRE])) {
            foreach ($this->registeredMiddlewares[self::MIDDLEWARE_PRE] as $middleware) {
                if ($allowedByPreMiddleware) {
                    $object = new $middleware();
                    $ret = $object->execute();
                    if ($ret === false) {
                        $allowedByPreMiddleware = false;
                        break;
                    }
                }
            }
        }

        if($allowedByPreMiddleware) {
            // handle CORS using the cors built-in middleware
            $corsPreflight = new \Nf\Middleware\CorsPreflight();
            $allowedByPreMiddleware &= $corsPreflight->execute();
        }
        
        if ($allowedByPreMiddleware) {
            // call the action
            call_user_func(array(
                $this->_controllerInstance,
                $this->_actionName . 'Action'
            ));
            $content = ob_get_clean();
            $this->_response->addBodyPart($content);
            
            // call post middlewares
            if (isset($activeRoute['middlewaresPost'])) {
                foreach ($activeRoute['middlewaresPost'] as $middleware) {
                    $object = new $middleware();
                    $object->execute();
                }
                unset($middleware);
            }
            // call post middlewares defined programatically, by instance
            if (isset($this->registeredMiddlewares[self::MIDDLEWARE_POST])) {
                foreach ($this->registeredMiddlewares[self::MIDDLEWARE_POST] as $middleware) {
                    $middleware->execute();
                }
            }
        }
        
      
    }
    
    // called after action
    public function postLaunchAction()
    {
        $reflected = new \ReflectionClass($this->_controllerInstance);
        if ($reflected->hasMethod('postLaunchAction')) {
            call_user_func(array(
                $this->_controllerInstance,
                'postLaunchAction'
            ), null);
        }
    }
}
