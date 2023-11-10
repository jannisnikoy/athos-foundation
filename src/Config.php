<?php

namespace Athos\Foundation;

/**
* Config
* Setup configuration based on environment
*
* @package  athos-foundation
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-foundation
*/

class Config {
    private $config;

    private $dbHost;
    private $dbUser;
    private $dbPass;
    private $dbName;

    private $version;

    public function __construct($configFile) {
        if(!isset($configFile) || !file_exists($configFile)) {
            echo '[ATHOS] Configuration file missing';
            exit();
        }

        $this->config = json_decode(file_get_contents($configFile));

        if(file_exists(SITE_PATH . '/version.json')) {
            $this->version = json_decode(file_get_contents(SITE_PATH . '/version.json'));
        }

        switch ($this->currentStage()) {
            case 'development':
                $this->development();
                break;
            case 'test':
                $this->test();
                break;
            case 'production':
                $this->production();
                break;
            default:
                $this->development();
                break;
        }
    }

    public function addModuleDir(string $directory) {
        array_push($this->config->module_dirs, $directory);
    }

    /**
    * Retrieves configuration properties.
    *
    * @return string Property value
    */
    public function get(string $key) {
        if(isset($this->config->$key)) {
            return $this->config->$key;
        }
        
        if(isset($this->$key)) {
            return $this->$key;
        }

        return null;
    }

    //
    // Private methods
    //

    /**
    * Configure properties for development environment.
    * Errors and exceptions will be displayed.
    */
    private function development() {
        ini_set('display_errors', '1');
        ini_set('error_reporting', E_ALL);

        $this->dbHost = $this->config->db->development->host;
        if(substr($_SERVER['REQUEST_URI'], 0, 5) != '/rest') {
            $this->dbUser = $this->config->db->development->adminUser;
        } else {
            $this->dbUser = $this->config->db->development->user;
        }
        $this->dbPass = $this->config->db->development->pass;
        $this->dbName = $this->config->db->development->name;
    }

    /**
    * Configure properties for test environment.
    * Errors and exceptions will be displayed.
    */
    private function test() {
        ini_set('display_errors', '0');
        ini_set('error_reporting', E_ALL);

        $this->dbHost = $this->config->db->test->host;
        if(substr($_SERVER['REQUEST_URI'], 0, 5) != '/rest') {
            $this->dbUser = $this->config->db->test->adminUser;
        } else {
            $this->dbUser = $this->config->db->test->user;
        }
        $this->dbPass = $this->config->db->test->pass;
        $this->dbName = $this->config->db->test->name;
    }

    /**
    * Configure properties for production environment.
    * Errors and exceptions will not be displayed.
    */
    private function production() {
        ini_set('display_errors', '0');
        ini_set('error_reporting',  0);

        $this->dbHost = $this->config->db->production->host;
        if(substr($_SERVER['REQUEST_URI'], 0, 5) != '/rest') {
            $this->dbUser = $this->config->db->production->adminUser;
        } else {
            $this->dbUser = $this->config->db->production->user;
        }
        $this->dbPass = $this->config->db->production->pass;
        $this->dbName = $this->config->db->production->name;
    }

    /**
    * Determins the current environment based on hostnames defined
    * in the configuration file.
    *
    * @return string environment name, or false if not found.
    */
    public function currentStage() {
        if (in_array($_SERVER['HTTP_HOST'], $this->config->domains->production)) {
            return 'production';
        } elseif(in_array($_SERVER['HTTP_HOST'], $this->config->domains->development)) {
            return 'development';
        } elseif(in_array($_SERVER['HTTP_HOST'], $this->config->domains->test)) {
            return 'test';
        }

        return false;
    }
}
?>
