<?php

namespace Athos\Foundation;

/**
* Authentication
* Provides basic session-based authentication.
*
* @package  athos-foundation
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-foundation
*/

class Auth {
    private $db;
    private $loggedIn;

    function __construct() {
        global $db;

        $this->db = $db;

        if (Session::valueForKey('APL_SESSION_ID')) {
            $this->validateSession(Session::valueForKey('APL_SESSION_ID'));
        }
    }

    /**
    * Initializes a login attempt
    *
    * @param string $username
    * @param string $password
    * @return bool true if attempt was succesful
    */
    public function login(string $username, string $password): bool {
        return $this->attemptLogin($username, $password);
    }

    /**
    * Deactives a user session and destroys the session object.
    */
    public function logout() {
        $this->loggedIn = false;

        if (Session::valueForKey('APL_SESSION_ID')) {
            $this->db->query("UPDATE apl_sessions SET isActive=0 WHERE id=?", Session::valueForKey('APL_SESSION_ID'));
        }

        Session::destroySession();
    }

    /**
    * Verifies if the user is logged in.
    *
    * @return true if user is logged in
    */
    public function loggedIn(): bool {
        if ($this->loggedIn) {
            return true;
        }

        return $this->attemptSessionLogin();
    }

    /**
    * Retrieves a username if a valid session is found.
    *
    * Default: 'Anonymous'
    *
    * @return string Username if found
    */
    public function getUsername(): string {
        if ($this->loggedIn) {
            $sessionId = Session::valueForKey('APL_SESSION_ID');

            $this->db->query("SELECT username FROM apl_users WHERE id=(SELECT userId FROM apl_sessions WHERE id=?)", $sessionId);
            return ucfirst($this->db->getRow()->username);
        }

        return 'Anonymous';
    }

    /**
    * Retrieves the current user credentials if a valid session is found.
    *
    * Default: 'none'
    *
    * @return string User credentials
    */
    public function getUserCredentials(): string {
        if ($this->loggedIn) {
            $sessionId = Session::valueForKey('APL_SESSION_ID');

            $this->db->query("SELECT role FROM apl_users WHERE id=(SELECT userId FROM apl_sessions WHERE id=?)", $sessionId);
            return $this->db->getRow()->role;
        }

        return 'none';
    }

    //
    // Private methods
    //

    /**
    * Attempts to validate a user session if a session ID is found.
    *
    * @return bool true if a valid session is found.
    */
    private function attemptSessionLogin(): bool {
        if (Session::hasValueForKey('APL_SESSION_ID')) {
			return $this->validateSession(Session::valueForKey('APL_SESSION_ID'));
		}

		return false;
    }

    /**
    * Validate a login attempt and create a session if succesful.
    * Password is stored using sha256 encryption.
    *
    * @param string $username
    * @param string $password
    */
    private function attemptLogin(string $username, string $password): bool {
        $this->db->query("SELECT * FROM apl_users WHERE username=? AND password=?", $username, hash('sha256', $password));

        if (!$this->db->hasRows()) {
            $this->loggedIn = false;
            return false;
        }

        $row = $this->db->getRow();

        $sessionId = md5($row->username . $row->password . time());

        $this->db->query("INSERT INTO apl_sessions(id, userId, expiresAt) VALUES(?, ?, FROM_UNIXTIME(?))", $sessionId, $row->id, time()+86400);
        $this->storeSessionData($sessionId);

        $this->loggedIn = true;

        return true;
    }

    /**
    * Verifies the existence of the requested session.
    *
    * @param string $sessionId User session ID
    * @return bool True if the session was validated
    */
    private function validateSession(string $sessionId): bool {
        $this->db->query("SELECT * FROM apl_sessions WHERE id=? AND expiresAt > NOW()", $sessionId);

        if ($this->db->hasRows()) {
            $this->db->query("UPDATE apl_sessions SET lastUpdatedAt=NOW() WHERE id=?", $sessionId);
            $this->loggedIn = true;
        } else {
            $this->logout();
        }

        return $this->loggedIn;
    }

    /**
    * Stores the sessionID in a PHP session.
    *
    * @param string $sessionId User session ID
    */
    private function storeSessionData(string $sessionId) {
        Session::setValueForKey('APL_SESSION_ID', $sessionId);
    }
}
?>
