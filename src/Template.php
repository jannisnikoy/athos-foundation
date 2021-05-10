<?php

namespace Athos\Foundation;

/**
* Template
* Load and render templates using Smarty
*
* @package  athos-foundation
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-foundation
*/

class Template {
    private $config;
    private $auth;
    private $smarty;

    public function __construct() {
        global $config, $auth, $smarty;

        $this->config = $config;
        $this->auth = $auth;
        $this->smarty = $smarty;
    }

    /**
    * Check is the requested view exists, and passes it onto the renderer.
    *
    * @param string $viewDir View directory of the module
    * @param string $moduleName Name of the module -- Will be used as view name
    * @param string $moduleAction Optional action name. View will override main view
    */
    public function loadTemplate(string $viewDir, string $moduleName, string $moduleAction = null) {
        if(file_exists($viewDir . $moduleAction . '.html')) {
            $this->render($viewDir . $moduleAction . '.html');
        } else if(file_exists($viewDir . $moduleName . '.html')){
            $this->render($viewDir . $moduleName . '.html');
        }
    }

    /**
    * Render the template using smarty
    *
    * @param string $file Full filename of the template to render
    */
    private function render(string $file) {
        if(!file_exists($file)) {
            return;
        }

        $this->smarty->assign('root', $this->config['site_root']);

        if ($this->auth->loggedIn()) {
            $this->smarty->assign('username', $this->auth->getUsername());
        }

        $custom_resources = '';

        $this->smarty->display($file);
    }
}
?>
