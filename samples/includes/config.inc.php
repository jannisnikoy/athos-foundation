<?php
    session_start();

    if(!defined('SITE_PATH')) define('SITE_PATH', realpath(dirname(__FILE__) . '/../'));
    require_once SITE_PATH . '/vendor/autoload.php';

    $config = new \Athos\Foundation\Config(SITE_PATH . '/includes/config.json');
    $db 	= new \Athos\Foundation\Database();
    $auth 	= new \Athos\Foundation\Auth();

    $smarty = new Smarty();
    $smarty->setCompileDir($config->get('cache_dir'));
?>
