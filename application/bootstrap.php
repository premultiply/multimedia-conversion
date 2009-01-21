<?php

defined('APPLICATION_PATH')
    or define('APPLICATION_PATH', dirname(__FILE__));

    
//zmienic na "production" na serwerze produkcyjnym
defined('APPLICATION_ENVIRONMENT')
    or define('APPLICATION_ENVIRONMENT', 'development');

$frontController = Zend_Controller_Front::getInstance();
$frontController->setControllerDirectory(APPLICATION_PATH . '/controllers');
$frontController->setParam('env', APPLICATION_ENVIRONMENT);

$configuration = new Zend_Config_Ini(
    APPLICATION_PATH . '/config/config.ini', 
    APPLICATION_ENVIRONMENT
);

$formats = new Zend_Config_Ini(
    APPLICATION_PATH . '/config/formats.ini', 
    APPLICATION_ENVIRONMENT
);

$dbAdapter = Zend_Db::factory($configuration->database);
Zend_Db_Table_Abstract::setDefaultAdapter($dbAdapter);

$registry = Zend_Registry::getInstance();
$registry->configuration = $configuration;
$registry->dbAdapter     = $dbAdapter;
$registry->formats       = $formats;

unset($frontController, $configuration, $registry, $formats);


    