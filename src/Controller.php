<?php

namespace Athos\Foundation;

/**
* Controller
* Main controller implementation to be used in modules
*
* @package  athos-foundation
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-foundation
*/

class Controller {
    public $config;
    public $auth;
    public $db;
    public $headers;
    public $smarty;
    public $executionStartTime;

    public function __construct() {
        global $config, $auth, $db, $smarty;

        $this->config = $config;
        $this->auth = $auth;
        $this->db = $db;
        $this->smarty = $smarty;
        $this->headers = getallheaders();
        $this->executionStartTime = microtime(true); 
    }

    /**
    * Determines whether or not the user needs authorization to access module.
    *
    * Default: true
    *
    * @return bool Returns true if the module requires the user to be logged in
    */
    public function requiresCredentials(): bool {
        return true;
    }

    /**
    * Determines the access levels permitted for a module.
    *
    * Default: ['admin']
    *
    * @return array Array of accepted credentials
    */
    public function acceptedCredentials(): array {
        return ['admin'];
    }
}
