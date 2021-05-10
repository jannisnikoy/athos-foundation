<?php
	ob_start();
	require_once '../includes/config.inc.php';

    $module = new Athos\Foundation\Module();

	if(isset($_GET['rt']) && !isset($_GET['action'])){
		$module->loadModule($_GET['rt']);
	}else if(isset($_GET['rt']) && isset($_GET['action'])){
	 	$module->loadModule($_GET['rt'], $_GET['action']);
	}else{
		$_GET['rt'] = "home";
		$module->loadModule('home');
	}

	ob_end_flush();
?>
