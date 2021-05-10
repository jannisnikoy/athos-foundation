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

    public function __construct() {
        global $config;

        $this->config = $config;
        $stage = $this->currentStage();

        switch (Config::currentStage()) {
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

    /**
    * Retrieves configuration properties.
    *
    * @return string Property value
    */
    public function get(string $key) {
        return $this->$key;
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

        $this->dbHost = $this->config['db']['development']['host'];
        $this->dbUser = $this->config['db']['development']['user'];
        $this->dbPass = $this->config['db']['development']['pass'];
        $this->dbName = $this->config['db']['development']['name'];
    }

    /**
    * Configure properties for test environment.
    * Errors and exceptions will be displayed.
    */
    private function test() {
        ini_set('display_errors', '1');
        ini_set('error_reporting', E_ALL);

        $this->dbHost = $this->config['db']['test']['host'];
        $this->dbUser = $this->config['db']['test']['user'];
        $this->dbPass = $this->config['db']['test']['pass'];
        $this->dbName = $this->config['db']['test']['name'];
    }

    /**
    * Configure properties for production environment.
    * Errors and exceptions will not be displayed.
    */
    private function production() {
        ini_set('display_errors', '0');
        ini_set('error_reporting', 0);

        $this->dbHost = $this->config['db']['production']['host'];
        $this->dbUser = $this->config['db']['production']['user'];
        $this->dbPass = $this->config['db']['production']['pass'];
        $this->dbName = $this->config['db']['production']['name'];
    }

    /**
    * Determins the current environment based on hostnames defined
    * in the configuration file.
    *
    * @return string environment name, or false if not found.
    */
    public static function currentStage() {
        global $config;

        if (in_array($_SERVER['HTTP_HOST'], $config['domains']['production'])) {
            return 'production';
        } elseif(in_array($_SERVER['HTTP_HOST'], $config['domains']['development'])) {
            return 'development';
        } elseif(in_array($_SERVER['HTTP_HOST'], $config['domains']['test'])) {
            return 'test';
        }

        return false;
    }
}
?>
