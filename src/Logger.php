<?php
namespace Athos\Foundation;

use JsonException;

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
    public static function printOutput(int $statusCode, mixed $response = null): void {
        global $db;

        http_response_code($statusCode);
        
        $options = JSON_NUMERIC_CHECK;
        if (isset($_GET['prettify']) && $_GET['prettify'] === 'true') {
            $options |= JSON_PRETTY_PRINT;
        }
        
        if($response != null) {
          try {
              $responseJson = json_encode($response, $options | JSON_THROW_ON_ERROR);
          } catch (JsonException $e) {
              http_response_code(500);
              echo json_encode(['error' => 'Failed to encode JSON'], JSON_THROW_ON_ERROR);
              return;
          }

          echo $responseJson;
        }

        try {
          $db->query("INSERT INTO exm_logs(status_code, method, ipaddress, path, headers, request, response) VALUES(?, ?, ?, ?, ?, ?, ?)", $statusCode, $_SERVER['REQUEST_METHOD'], $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'], json_encode(getallheaders()), file_get_contents('php://input'), json_encode($response));
        } catch (Exception $e) {
          
        }
      }
    }

?>