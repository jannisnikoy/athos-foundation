<?php

namespace Athos\Foundation;

/**
* Session
* Manage session data
*
* @package  athos-foundation
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-foundation
*/

class Session {
    /**
    * Assign a value to the provided key. Existing value will be overwritten.
    *
    * @param string $key
    * @param $value
    */
    public static function setValueForKey(string $key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
    * Looks up the provided key to see if a value is present
    *
    * @param string $key
    * @return bool true if found
    */
    public static function hasValueForKey(string $key): bool {
        if (isset($_SESSION[$key]) && $_SESSION[$key] != '') {
            return true;
        }

        return false;
    }

    /**
    * Retrieves the value for a key.
    *
    * @param string $key
    * @return Value for provided key, or null if not found.
    */
    public static function valueForKey(string $key) {
        if (self::hasValueForKey($key)) {
            return $_SESSION[$key];
        }

        return null;
    }

    /**
    * Removes the value a key.
    *
    * @param string $key
    */
    public static function removeValueForKey(string $key) {
        unset($_SESSION[$key]);
    }

    /**
    * Completely destroys the user session
    */
    public static function destroySession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
?>
