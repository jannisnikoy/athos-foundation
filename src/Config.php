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
    private $stage;

    public function __construct($configFile) {
        if(!isset($configFile) || !file_exists($configFile)) {
            echo '[ATHOS] Configuration file missing';
            exit();
        }

        $this->config = json_decode(file_get_contents($configFile));

        if(file_exists(SITE_PATH . '/version.json')) {
            $this->version = json_decode(file_get_contents(SITE_PATH . '/version.json'));
        }

        $stage = $this->currentStage();
        $this->stage = $stage;
        $this->setupEnvironment($this->config->environments->$stage);
    }

    public function addModuleDir(string $directory) {
        array_push($this->config->module_dirs, $directory);
    }

    public function getEnvironmentVariable(string $key) {
        $stage = $this->stage;

        if(isset($this->config->environments->$stage->keys->$key)) {
            return $this->config->environments->$stage->keys->$key;
        }

        if(isset($this->config->keys->$key)) {
            return $this->config->keys->$key;
        }

        return null;
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
    
    private function setupEnvironment($environment) {
        ini_set('display_errors', isset($environment->error_reporting) && $environment->error_reporting == true ? 1 : 0);
        ini_set('error_reporting', isset($environment->error_reporting) && $environment->error_reporting == true ? E_ALL : E_NONE);

        $this->dbHost = $environment->db->host;
        if(substr($_SERVER['REQUEST_URI'], 0, 5) != '/rest') {
            $this->dbUser = $environment->db->adminUser;
        } else {
            $this->dbUser = $environment->db->user;
        }
        $this->dbPass = $environment->db->pass;
        $this->dbName = $environment->db->name;
    }

    /**
    * Determins the current environment based on hostnames defined
    * in the configuration file.
    *
    * @return string environment name, or false if not found.
    */
    public function currentStage() {
        foreach(array_keys(get_object_vars($this->config->environments)) as $environment) {
            if (in_array($_SERVER['HTTP_HOST'], $this->config->environments->$environment->domains)) {
                return $environment;
            }
        }

        return false;
    }
}
?>
