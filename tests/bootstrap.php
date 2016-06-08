<?php
$libraryPath = realpath(dirname(__FILE__) . '/../');
$applicationPath = realpath(dirname(__FILE__) . '/testsite');

$applicationNamespace = 'App';

require ($libraryPath . '/Nf/Autoloader.php');
$autoloader = new \Nf\Autoloader();
$autoloader->addNamespaceRoot($applicationNamespace, $applicationPath . '/models');
$autoloader->addNamespaceRoot('Nf', $libraryPath . '/Nf');
$autoloader->addNamespaceRoot('Library', $libraryPath . '/php/models');
$autoloader->addNamespaceRoot('', $applicationPath . '/models');
$autoloader->register();

\Nf\Registry::set('libraryPath', $libraryPath);
\Nf\Registry::set('applicationPath', $applicationPath);

$urlIni = \Nf\Ini::parse(\Nf\Registry::get('applicationPath') . '/configs/url.ini', true);
\Nf\Registry::set('urlIni', $urlIni);

\Nf\Registry::set('environment', 'test');
\Nf\Registry::set('locale', $urlIni->i18n->defaultLocale);
\Nf\Registry::set('version', 'cli');

$config = \Nf\Ini::parse(\Nf\Registry::get('applicationPath') . '/configs/config.ini', true, \Nf\Registry::get('locale') . '_' . \Nf\Registry::get('environment') . '_' . \Nf\Registry::get('version'));
\Nf\Registry::set('config', $config);

\Nf\Error\Handler::setErrorDisplaying();
$front = \Nf\Front::getInstance();

$request = new \Nf\Front\Request\Cli('/');
$front->setRequest($request);

$response = new \Nf\Front\Response\Cli();
$front->setResponse($response);
$front->setApplicationNamespace($applicationNamespace);

// routing
$router = \Nf\Router::getInstance();
$front->setRouter($router);
$router->addAllRoutes();

$labelManager = \Nf\LabelManager::getInstance();
$labelManager->loadLabels(\Nf\Registry::get('locale'));

$localization = \Nf\Localization::getInstance();
$localization->setLocale(\Nf\Registry::get('locale'));