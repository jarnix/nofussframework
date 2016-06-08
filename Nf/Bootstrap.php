<?php
namespace Nf;

use Nf\Localization;

/**
 * Bootstrap is responsible for instanciating the application in cli or web environment
 *
 * @package Nf
 *
 */
class Bootstrap
{

    const DEFAULT_LOCALESELECTIONORDER = 'cookie,domain,browser';

    private $_localeAndVersionFromDomainCache = null;

    private $_applicationNamespace = 'App';

    public function __construct($libraryPath, $applicationPath)
    {
        Registry::set('libraryPath', $libraryPath);
        Registry::set('applicationPath', $applicationPath);
    }

    public function initHttpEnvironment($inEnvironment = null, $inLocale = null, $inVersion = null)
    {
        $urlIni = Ini::parse(Registry::get('applicationPath') . '/configs/url.ini', true);
        Registry::set('urlIni', $urlIni);

        // environment: dev, test, or prod
        if (! empty($inEnvironment)) {
            $environment = $inEnvironment;
        } else {
            // by default
            $environment = $urlIni->defaults->environment;

            if (! empty($_SERVER['HTTP_HOST'])) {
                // let's check the environment from the http host (and set the other values)
                list ($localeFromDomain, $versionFromDomain, $environmentFromDomain) = $this->getLocaleAndVersionAndEnvironmentFromDomain($_SERVER['HTTP_HOST'], $urlIni);
                if (!empty($environmentFromDomain)) {
                    $environment = $environmentFromDomain;
                }
            }
        }

        if (! empty($inLocale)) {
            $locale = $inLocale;
        } else {
            // locale selection order
            if (! empty($urlIni->localeSelectionOrder->$environment)) {
                $localeSelectionOrder = $urlIni->localeSelectionOrder->$environment;
            } else {
                $localeSelectionOrder = self::DEFAULT_LOCALESELECTIONORDER;
            }
            $localeSelectionOrderArray = (array) explode(',', $localeSelectionOrder);
            // 3 possibilities : according to the url, or by a cookie, or by the browser's accept language

            $locale = null;
            foreach ($localeSelectionOrderArray as $localeSelectionMethod) {
                if (! in_array($localeSelectionMethod, array(
                    'browser',
                    'domain',
                    'cookie'
                ))) {
                    throw new \Exception('The locale selection method must be chosen from these values: browser and/or domain and/or cookie');
                }
                if (empty($locale)) {
                    switch ($localeSelectionMethod) {
                        case 'browser':
                            // we use the locale of the browser if requested to
                            if (! empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                                // vérification de la syntaxe par une regexp
                                if (preg_match('/[a-z]+[_\-]?[a-z]+[_\-]?[a-z]+/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches)) {
                                    $locale = Localization::normalizeLocale($matches[0]);
                                }
                            }
                            break;
                        case 'domain':
                            // we will read the url.ini to guess the requested locale
                            // according to the domain name
                            if (! empty($_SERVER['HTTP_HOST'])) {
                                list ($localeFromDomain, $versionFromDomain, $environmentFromDomain) = $this->getLocaleAndVersionAndEnvironmentFromDomain($_SERVER['HTTP_HOST'], $urlIni);
                                if (! empty($localeFromDomain)) {
                                    $locale = $localeFromDomain;
                                }
                            }
                            break;
                        case 'cookie':
                            // read the cookie to select the locale
                            if (! empty($_COOKIE['_nfLc'])) {
                                // matching of the locale with the cookie's value
                                if (preg_match('/[a-z]+[_\-]?[a-z]+[_\-]?[a-z]+/i', $_COOKIE['_nfLc'], $matches)) {
                                    $locale = Localization::normalizeLocale($matches[0]);
                                }
                            }
                            break;
                    }
                } else {
                    break;
                }
            }

            // if we did not find the locale with the http host or cookie or browser, let's use the default value
            if ($locale === null) {
                if (! empty($urlIni->defaults->locale)) {
                    $locale = $urlIni->defaults->locale;
                } else {
                    throw new \Exception('Locale not found from browser, cookie or url: you have to set a default locale in url.ini');
                }
            }
        }

        // version (web, mobile, cli...)
        if (empty($inVersion)) {
            if (! empty($versionFromDomain)) {
                $version = $versionFromDomain;
            } else {
                if (in_array('url', $localeSelectionOrderArray)) {
                    if (! empty($_SERVER['HTTP_HOST'])) {
                        list ($localeFromDomain, $versionFromDomain, $environmentFromDomain) = $this->getLocaleAndVersionAndEnvironmentFromDomain($_SERVER['HTTP_HOST'], $urlIni);
                    }
                }
                if (! empty($versionFromDomain)) {
                    $version = $versionFromDomain;
                } else {
                    // let's take the default version then
                    if (isset($urlIni->defaults->version)) {
                        $version = $urlIni->defaults->version;
                    } else {
                        trigger_error('Cannot guess the requested version from the domain name: you have to set a default locale in url.ini');
                    }
                }
            }
        } else {
            $version = $inVersion;
        }

        // on assigne les variables d'environnement et de language en registry
        Registry::set('environment', $environment);
        Registry::set('locale', $locale);
        Registry::set('version', $version);

        // we use the requested section from the config.ini to load our config
        Config::init($locale, $environment, $version);
        Registry::set('config', Config::getInstance());
                        
        // parse the variables from the .env file or environment
        $env = Env::init($locale, $environment, $version);
        Registry::set('env', Env::getInstance());
        
        // create the Settings Object
        Registry::set('settings', Settings::getInstance());

        // let's block the use of index.php
        if (isset($_SERVER['REQUEST_URI']) && in_array($_SERVER['REQUEST_URI'], array(
            'index.php',
            '/index.php'
        ))) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: /");
            return false;
        }

        return true;
    }

    private function getLocaleAndVersionAndEnvironmentFromDomain($httpHost, $urlIni)
    {
        $httpHost = mb_strtolower($httpHost);

        if (! empty($this->_localeAndVersionFromDomainCache)) {
            return $this->_localeAndVersionFromDomainCache;
        } else {
            $localeFromDomain = '';
            $versionFromDomain = '';
            $environmentFromDomain = '';

            foreach ($urlIni->regexps as $localeEnvironmentVersion => $regexp) {
                if (strpos($localeEnvironmentVersion, '-') === false) {
                    throw new \Exception('You must name the localeEnvironmentVersion strings <locale>-<environment>-<version>');
                }
                if (preg_match($regexp, $httpHost)) {
                    list ($localeFromDomain, $environmentFromDomain, $versionFromDomain) = explode('-', $localeEnvironmentVersion);
                    break;
                }
            }

            $this->_localeAndVersionFromDomainCache = array(
                $localeFromDomain,
                $versionFromDomain,
                $environmentFromDomain
            );
        }

        return array(
            $localeFromDomain,
            $versionFromDomain,
            $environmentFromDomain
        );
    }

    public function setApplicationNamespace($namespace)
    {
        $this->_applicationNamespace = $namespace;
        \Nf\Registry::set('applicationNamespace', $namespace);
    }

    public function initCliEnvironment()
    {
        $showUsage = true;

        if (isset($_SERVER['argv']) && $_SERVER['argc'] >= 2) {
            $urlIni = Ini::parse(Registry::get('applicationPath') . '/configs/url.ini', true);
            Registry::set('urlIni', $urlIni);

            $inEnvironment = $urlIni->defaults->environment;
            $inLocale = $urlIni->defaults->locale;
            $inVersion = 'cli';
            $inAction = array(
                'type' => 'default',
                'uri' => null
            );

            // default values
            Registry::set('environment', $inEnvironment);
            Registry::set('locale', $inLocale);
            Registry::set('version', $inVersion);
            
            // we use the requested section from the config.ini to load our config
            Config::init($inLocale, $inEnvironment, $inVersion);
            Registry::set('config', Config::getInstance());
                            
            // parse the variables from the .env file or environment
            $env = Env::init($inLocale, $inEnvironment, $inVersion);
            Registry::set('env', Env::getInstance());
            
            // create the Settings Object
            Registry::set('settings', Settings::getInstance());

            $arrParams = array();

            $ac = 1;
            while ($ac < (count($_SERVER['argv']))) {
                switch ($_SERVER['argv'][$ac]) {
                    case '-e':
                    case '--environment':
                        $inEnvironment = $_SERVER['argv'][$ac + 1];
                        $ac += 2;
                        break;
                    case '-l':
                    case '--locale':
                        $inLocale = $_SERVER['argv'][$ac + 1];
                        $ac += 2;
                        break;
                    case '-v':
                    case '--version':
                        $inVersion = $_SERVER['argv'][$ac + 1];
                        $ac += 2;
                        break;
                    case '-a':
                    case '--action':
                        $inAction['uri'] = ltrim($_SERVER['argv'][$ac + 1], '/');
                        $ac += 2;
                        $showUsage = false;
                        break;
                    case '-m':
                    case '--make':
                        $inAction['uri'] = ltrim($_SERVER['argv'][$ac + 1], '/');
                        $inAction['type'] = 'make';
                        $showUsage = false;
                        $ac += 2;
                        break;
                    default:
                        $ac += 2;
                        break;
                }
            }
        }

        if (! $showUsage) {
            // on lit le config.ini à la section concernée par notre environnement
            $config = Ini::parse(Registry::get('applicationPath') . '/configs/config.ini', true, $inLocale . '-' . $inEnvironment . '-' . $inVersion);
            Registry::set('config', $config);

            // on assigne les variables d'environnement et de langue en registry
            Registry::set('environment', $inEnvironment);
            Registry::set('locale', $inLocale);
            Registry::set('version', $inVersion);

            return $inAction;
        } else {
            echo "Usage : module/controller/action";
            echo "\nOr : module/controller/action -variable1 value1 -variable2 value2 -variable3 value3";
            echo "\nOr : module/controller/action/variable1/value1/variable2/value2/variable3/value3";
            exit(04);
        }
    }

    function go()
    {
        if (php_sapi_name() == 'cli') {
            $inAction = $this->initCliEnvironment();

            $uri = $inAction['uri'];
            Error\Handler::setErrorDisplaying();
            $front = Front::getInstance();

            $request = new Front\Request\Cli($uri);
            $front->setRequest($request);

            $request->setAdditionalCliParams();

            $response = new Front\Response\Cli();

            $front->setResponse($response);
            $front->setApplicationNamespace($this->_applicationNamespace);

            $this->setTimezone();

            // routing
            $router = Router::getInstance();
            $front->setRouter($router);
            $router->addAllRoutes();

            // order in finding routes
            $router->setStructuredRoutes();

            $front->addModuleDirectory($this->_applicationNamespace, Registry::get('applicationPath') . '/application/cli/');
            $front->addModuleDirectory('library', Registry::get('libraryPath') . '/php/application/cli/');

            $labelManager = LabelManager::getInstance();
            $labelManager->loadLabels(Registry::get('locale'));

            $localization = Localization::getInstance();
            $localization->setLocale(Registry::get('locale'));

            if ($inAction['type'] == 'default') {
                $testDispatch = $front->dispatch();

                if ($testDispatch) {
                    if ($front->init() !== false) {
                        $front->launchAction();
                        $front->postLaunchAction();
                    }
                    $response->sendResponse();
                } else {
                    throw new \Exception('Action not found : ' . $uri);
                }
            } else {
                $front->parseParameters($inAction['uri']);
                $className = array();

                // $inAction['uri'] might be a class name with a static method like \Nf\Make::compress
                if ((strpos($inAction['uri'], '\\') !== false)) {
                    if (strpos($inAction['uri'], '::') === false) {
                        throw new \Exception('You have to specify the model and method to call, or just choose a method from the "Nf\Make" class.');
                    } else {
                        $uriSplit = explode('::', $inAction['uri']);
                        $className = $uriSplit[0];
                        $methodName = $uriSplit[1];
                        $obj = new $className();
                        $className::$methodName();
                    }
                } else {
                    // or an already integrated method in Nf\Make
                    $methodName = $inAction['uri'];
                    \Nf\Make::$methodName();
                }
            }
        } else {
            $this->initHttpEnvironment();

            Error\Handler::setErrorDisplaying();

            $front = Front::getInstance();
            $request = new Front\Request\Http();

            $front->setRequest($request);
            $response = new Front\Response\Http();
            $front->setResponse($response);
            $front->setApplicationNamespace($this->_applicationNamespace);

            $this->setTimezone();

            // routing
            $router = Router::getInstance();
            $front->setRouter($router);
            $router->addAllRoutes();

            // order in finding routes
            $router->setRoutesFromFiles();
            $router->setRootRoutes();
            $router->setStructuredRoutes();

            // modules directory for this version
            $front->addModuleDirectory($this->_applicationNamespace, Registry::get('applicationPath') . '/application/' . Registry::get('version') . '/');
            $front->addModuleDirectory('library', Registry::get('libraryPath') . '/php/application/' . Registry::get('version') . '/');

            $config = Registry::get('config');
            if (isset($config->session->handler)) {
                $front->setSession(Session::start());
            }

            $labelManager = LabelManager::getInstance();
            $labelManager->loadLabels(Registry::get('locale'));

            $localization = Localization::getInstance();
            Localization::setLocale(Registry::get('locale'));

            $testDispatch = $front->dispatch();

            $requestIsClean = $request->sanitizeUri();

            if ($requestIsClean) {
                if ($testDispatch === true) {
                    $request->setPutFromRequest();

                    if (! $request->redirectForTrailingSlash()) {
                        if ($front->init() !== false) {
                            if (! $front->response->isRedirect()) {
                                $front->launchAction();
                            }
                            if (! $front->response->isRedirect()) {
                                $front->postLaunchAction();
                            }
                        }
                    }
                } else {
                    Error\Handler::handleNotFound(404);
                }
            } else {
                Error\Handler::handleForbidden(403);
            }
            $response->sendResponse();
        }
    }

    private function setTimezone()
    {
        $config = Registry::get('config');
        if (isset($config->date->timezone)) {
            try {
                date_default_timezone_set($config->date->timezone);
            } catch (\Exception $e) {
                echo 'timezone set failed (' . $config->date->timezone . ') is not a valid timezone';
            }
        }
    }
}
