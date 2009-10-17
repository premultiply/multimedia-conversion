<?php

define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application/'));
set_include_path(
   	realpath(dirname(__FILE__) . '/../library/') //sciezka do zend frameworka
    . PATH_SEPARATOR . 
    APPLICATION_PATH . '/models'
    . PATH_SEPARATOR .
    get_include_path()
);

/*require_once "Zend/Loader.php";
Zend_Loader::registerAutoload();*/

require_once 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_Autoloader::getInstance();
$loader->registerNamespace('App_');


$loader->setFallbackAutoloader(true);

$loader->suppressNotFoundWarnings(false);

try {
    require '../application/bootstrap.php';
} catch (Exception $exception) {
    echo '<html><body><center>'
       . 'An exception occured while bootstrapping the application.';
    if (defined('APPLICATION_ENVIRONMENT')
        && APPLICATION_ENVIRONMENT != 'production'
    ) {
        echo '<br /><br />' . $exception->getMessage() . '<br />'
           . '<div align="left">Stack Trace:' 
           . '<pre>' . $exception->getTraceAsString() . '</pre></div>';
    }
    echo '</center></body></html>';
    exit(1);
}

Zend_Controller_Front::getInstance()->dispatch();

