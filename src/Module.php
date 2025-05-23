<?php

namespace Athos\Foundation;

/**
* Module
* Verify existence of a requested module and initializes it. Passes module
* on to Template for view rendering.
*
* @package  athos-foundation
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-foundation
*/

class Module {
    private $config;
    private $auth;

    private $moduleName;
    private $moduleAction;

    private $moduleDir;
    private $moduleFile;

    private $viewDir;
    private $viewFile;

    private $smarty;

    public function __construct() {
        global $config;
        global $auth;
        global $smarty;

        $this->config = $config;
        $this->auth = $auth;
        $this->smarty = $smarty;
    }

    /**
    * Verifies if the requested module exists. If not, error module is loaded.
    * If the module has an existing view, Template is called to render the view.
    *
    * @param string $moduleName Name of the module
    * @param string $moduleAction Optional action to load within the module
    */
    public function loadModule(string $moduleName, string $moduleAction = null): void {
        $this->moduleName = $moduleName;
        $this->moduleAction = $moduleAction;

        $this->loadDefaultController();

        if ($this->moduleExists()) {
            if(isset($_GET['rt']) && in_array($_GET['rt'], $this->config->get('disabled_modules') ?? []) && $moduleName != 'error') {
                $module = new Module();
                $module->loadModule('error');
                return;
            }
            
            include $this->moduleFile;

            $this->loadController($this->moduleName, $this->moduleAction);

            if (isset($this->viewDir)) {
                $template = new Template();
                $template->loadTemplate($this->viewDir, $this->moduleName, $this->moduleAction);
            }
        } else {
            $module = new Module();
            $module->loadModule('error');
            return;
        }
    }

    //
    // Private methods
    //

    /**
    * Checks if the requested module exists in either a dedicated module directory,
    * or as a standalone file in the generic modules root.
    *
    * @return bool Returns true if the requested module is found
    */
    private function moduleExists(): bool {
        foreach ($this->config->get('module_dirs') as $directory) {
            $moduleControllerFile = $directory . strtolower($this->moduleName) . '/controllers/' . ucfirst(isset($this->moduleAction) ? $this->moduleAction : $this->moduleName) . 'Controller.php';
            $standaloneControllerFile = $directory . ucfirst($this->moduleName) . 'Controller.php';
            $mainControllerFile = $directory . strtolower($this->moduleName) . '/controllers/' . ucfirst($this->moduleName) . 'Controller.php';
            $dataControllerFile = $directory . strtolower($this->moduleName) . '/data/' . ucfirst(isset($this->moduleAction) ? $this->moduleAction : $this->moduleName) . 'Controller.php';

            if(file_exists($dataControllerFile)) {
                $this->moduleDir = $directory;
                $this->moduleFile = $dataControllerFile;
                
                return true;
            }

            if (file_exists($directory . strtolower($this->moduleName) . '/views/' . strtolower($this->moduleName) . '.html')) {
                $this->viewDir = $directory . strtolower($this->moduleName) . '/views/';
            }

            if (file_exists($moduleControllerFile)) {
                $this->moduleDir = $directory .  strtolower($this->moduleName) . '/controllers/';
                $this->moduleFile = $moduleControllerFile;
                return true;
            } else if (file_exists($standaloneControllerFile)) {
                $this->moduleDir = $directory;
                $this->moduleFile = $standaloneControllerFile;
                return true;
            } else if(file_exists($mainControllerFile)) {
                $this->moduleDir = $directory;
                $this->moduleFile = $mainControllerFile;
                return true;
            }
        }

        return false;
    }

    /**
    * Initialize the module controller. If credential requirements are set, it verifies if the user
    * has authorization and/or is logged in. If not, the appropriate page will be loaded instead of
    * the requested module.
    *
    * @param string $moduleName Name of module
    * @param string $moduleAction Optional action to load within the module
    * @param bool $checkCredentials Allows for an override of credential checks
    */
    private function loadController(string $moduleName, string $moduleAction = null, bool $checkCredentials = true): void {
        if(class_exists(ucfirst(isset($moduleAction) ? $moduleAction : $moduleName) . 'Controller')) {
            $controller = ucfirst(isset($moduleAction) ? $moduleAction : $moduleName) . 'Controller';
            $controller = new $controller();
        }else if(class_exists(ucfirst($moduleName) . 'Controller')) {
            $controller = ucfirst($moduleName) . 'Controller';
            $controller = new $controller();
        } else {
            $module = new Module();

            $module->loadModule('error');
            exit();
        }

        if ($checkCredentials) {
            $requiresCredentials = $controller->requiresCredentials();
            $acceptedCredentials = $controller->acceptedCredentials();

            if ($requiresCredentials && $moduleName != 'login' && !$this->auth->loggedIn()) {
                $returnUri = '';

                if($_SERVER['REQUEST_URI'] != '/' && $_SERVER['REQUEST_URI'] != $this->config->get('site_root') . '/' && $_SERVER['REQUEST_URI'] != $this->config->get('site_root')) {
                    $returnUri = '?returnUri=' . str_replace($this->config->get('site_root') . '/', '/', $_SERVER['REQUEST_URI']);
                }

               header('Location: ' . $this->config->get('site_root') . '/login' . $returnUri);
               return;
            }

            if (!in_array($this->auth->getUserCredentials(), $acceptedCredentials) && $requiresCredentials) {
                $module = new Module();
                $module->loadModule('error');
                exit();
            }
        }

        if (isset($_GET['action']) && method_exists($controller, $_GET['action'].'Action')) {
            $action = strtolower($_GET['action']) . 'Action';
            $controller->$action();
        } else {
            if (method_exists($controller, 'defaultAction')) {
                $controller->defaultAction();
            }
        }
    }

    /**
    * Attempts to find the default controller and loads it if found.
    */
    private function loadDefaultController(): void {
        foreach ($this->config->get('module_dirs') as $directory) {

            if(file_exists($directory . '/AthosController.php')) {
                require_once $directory . '/AthosController.php';
                $this->loadController('Athos', null, false);
            }
        }

        if(file_exists(SITE_PATH . '/modules/DefaultController.php')) {
            require_once SITE_PATH.'/modules/DefaultController.php';
            $this->loadController('Default', null, false);
        }
    }
}
