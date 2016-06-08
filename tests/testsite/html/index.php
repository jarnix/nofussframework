<?php

error_reporting(E_ALL);

/*********************************************************
* Includes
* *******************************************************/
$libraryPath = realpath(dirname(__FILE__) . '/../../library');
$applicationPath = realpath(dirname(__FILE__) . '/..');

/*********************************************************
* My application
* ********************************************************/
$applicationNamespace='App';

/*********************************************************
* Autoloader
* *******************************************************/
$nfAllFile = $applicationPath . '/cache/Nf.all.php';
if(file_exists($nfAllFile)) {
    require($nfAllFile);
}
else {
    require($libraryPath . '/php/classes/Nf/Autoloader.php');
}
$autoloader=new \Nf\Autoloader();
$autoloader->addMap();
$autoloader->addNamespaceRoot('Nf', $libraryPath . '/php/classes/Nf');
$autoloader->addNamespaceRoot('', $libraryPath . '/php/classes');
$autoloader->addNamespaceRoot('', $libraryPath . '/php/models');
$autoloader->addNamespaceRoot($applicationNamespace, $applicationPath . '/models');
$autoloader->addNamespaceRoot('Library', $libraryPath . '/php/models');
$autoloader->register();
/******************************************************* */

$bootstrap=new \Nf\Bootstrap($libraryPath, $applicationPath);
\Nf\Error\Handler::setErrorHandler();
$bootstrap->setApplicationNamespace($applicationNamespace);

$bootstrap->go();
