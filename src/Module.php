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

    public function __construct() {
        global $config;
        global $auth;

        $this->config = $config;
        $this->auth = $auth;
    }

    /**
    * Verifies if the requested module exists. If not, error module is loaded.
    * If the module has an existing view, Template is called to render the view.
    *
    * @param string $moduleName Name of the module
    * @param string $moduleAction Optional action to load within the module
    */
    public function loadModule(string $moduleName, string $moduleAction = null) {
        $this->moduleName = $moduleName;

        if ($this->moduleExists()) {
            include $this->moduleFile;
            $this->loadController($moduleName);

            $template = new Template();
            $template->loadTemplate($this->viewDir, $moduleName, $moduleAction);
        } else {
            new Module('error');
            return;
        }

        $this->loadDefaultController();
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
        $files = array(
            '../' . $this->config['module_dir']  . '/' . strtolower($this->moduleName) . '/',
            '../modules/'
        );

        foreach ($files as $directory) {
            if (file_exists($directory . '/views/' . strtolower($this->moduleName) . '.html')) {
                $this->viewDir = $directory . '/views/';
            }

            $moduleFile = $directory . '/controllers/' . ucfirst($this->moduleName) . 'Controller.php';
            if (file_exists($moduleFile)) {
                $this->moduleDir = $directory .  '/controllers/';
                $this->moduleFile = $moduleFile;
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
    private function loadController(string $moduleName, string $moduleAction = null, bool $checkCredentials = true) {
        $controller = ucfirst($moduleName) . 'Controller';
        $controller = new $controller();

        if ($checkCredentials) {
            $requiresCredentials = $controller->requiresCredentials();
            $acceptedCredentials = $controller->acceptedCredentials();

            if ($requiresCredentials && isset($_GET) && $_GET['rt'] != 'login' && !$this->auth->loggedIn()) {
               header("Location: /?rt=login");
               return;
            }

            if (!in_array($this->auth->getUserCredentials(), $acceptedCredentials) && $requiresCredentials) {
               header("Location: /unauthorized");
               return;
            }
        }

        if ($moduleAction != null) {
            $moduleAction = $moduleAction . 'Action';
            $controller->$moduleAction();
        }
    }

    /**
    * Attempts to find the default controller and loads it if found.
    */
    private function loadDefaultController() {
        if(file_exists('../modules/DefaultController.php')) {
            include '../modules/DefaultController.php';
            $this->loadController('Default', null, false);
        }
    }
}
