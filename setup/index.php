<?php

/**
 * PHP Social Stream 2.9.0
 * Copyright 2015-2019 Axent Media (support@axentmedia.com)
 */

session_start();
header("Cache-Control: max-age=300, must-revalidate");

# Define root path
define( 'ROOT_PATH', realpath('..') );
define( 'INSTALL_PATH', dirname(__FILE__) );
define( 'INSTALLER_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'installer' );

# Define templates path
if ( !defined('TMPL_PATH') )
{
    define('TMPL_PATH', INSTALLER_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR);
}

# Define base url
define( 'BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
define( 'HOME_URL', strtok(BASE_URL, '?') );
define( 'API_PAGE_URL', HOME_URL.'?step=api' );

define('DOC_URL', 'https://axentmedia.com/php-social-stream-docs/');
define('TOKEN_SERVER', 'https://axentmedia.com/');

# Error reporting
ini_set('display_errors', true);
error_reporting(E_ALL);

$config_file = file_exists('../config.php') ? '../config.php' : '../config-sample.php';
require($config_file);

# Display
require_once(INSTALLER_PATH . '/Installer.php');
$installer = new Installer();
$installer->display();
