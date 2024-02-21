<?php

namespace Athos\Foundation;

/**
* Logger
* Log REST requests and responses
*
* @package  athos-foundation
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-foundation
*/

class Logger {
    /**
    * Log data to exm_logs and print output in JSON
    *
    * @param int $statusCode HTTP status code
    * @param mixed $response Optional response payload
    */
    public static function printOutput($statusCode, $response) {
        global $db;

        http_response_code($statusCode);
        if(isset($response)) {
          echo json_encode($response, isset($_GET['prettify']) && $_GET['prettify'] == 'true' ? JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT : JSON_NUMERIC_CHECK);
        }
        $db->query("INSERT INTO exm_logs(status_code, method, ipaddress, path, headers, request, response) VALUES(?, ?, ?, ?, ?, ?, ?)", $statusCode, $_SERVER['REQUEST_METHOD'], $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'], json_encode(getallheaders()), file_get_contents('php://input'), json_encode($response));
      }
}
?>
