<?php
    /**
    * WARNING: DO NOT COMMIT USER CREDENTIALS INTO SOURCE CONTROL!
    */
    session_start();

    if(!defined('SITE_PATH')) define('SITE_PATH', realpath(dirname(__FILE__) . '/../'));
    require_once SITE_PATH . '/vendor/autoload.php';

    $config = array();
    $config['site_root'] = '';
    $config['module_dirs'] = array('../modules/');

    $config['db'] = array(
        'development' => array(
            'host' => 'localhost',
            'user' => 'username',
            'pass' => 'password',
            'name' => 'database'
        ),
        'test' => array(
            'host' => 'localhost',
            'user' => 'username',
            'pass' => 'password',
            'name' => 'database'
        ),
        'production' => array(
            'host' => 'localhost',
            'user' => 'username',
            'pass' => 'password',
            'name' => 'database'
        )
    );

    $config['domains'] = array(
        'development' => array('localhost'),
        'test' => array('test.example.com'),
        'production' => array('example.com')
    );

    $appConfig = new \Athos\Foundation\Config();
    $db 	= new \Athos\Foundation\Database($appConfig->get('dbHost'), $appConfig->get('dbUser'), $appConfig->get('dbPass'), $appConfig->get('dbName'));
    $auth 	= new \Athos\Foundation\Auth();
    $smarty = new Smarty();
    $smarty->setCompileDir('../cache');
?>
