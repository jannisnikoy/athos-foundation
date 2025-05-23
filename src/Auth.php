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
    private $config;
    private $db;
    private $loggedIn;
    private $ttl;

    function __construct() {
        global $config, $db;

        $this->config = $config;
        $this->db = $db;
        $this->ttl = 86400;

        if ($this->config->get('session_ttl') != null && is_int($this->config->get('session_ttl'))) {
            $this->ttl = $this->config->get('session_ttl');
        }

        if ($this->attemptSessionLogin()) {
            return;
        }

        if ($this->shouldUseCookies()) {
            $this->attemptCookieLogin();
        }
    }


    /**
    * Checks if a user account exists and registers if not.
    *
    * @param string $username
    * @param string $password
    * @return bool true if registration was succesful
    */
    public function register(string $username, string $password): bool {
        $this->db->query('SELECT * FROM exm_users WHERE username=? AND password=?', $username, hash('sha256', $password));

        if ($this->db->hasRows()) {
            return false;
        }

        $this->db->query('INSERT INTO exm_users(username, password) VALUES(?, ?)', $username, hash('sha256', $password));

        return $this->attemptLogin($username, $password);
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
    public function logout(): void {
        $this->loggedIn = false;

        if (Session::valueForKey('ATHOS_SESSION_ID')) {
            $this->db->query('UPDATE exm_sessions SET is_active=false WHERE id=?', Session::valueForKey('ATHOS_SESSION_ID'));
        }

        Session::destroySession();
        setcookie('athos', '.', time() - $this->ttl, '/', 'localhost');
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

        if ($this->attemptSessionLogin()) {
            return true;
        }

        if ($this->shouldUseCookies()) {
            return $this->attemptCookieLogin();
        }

        return false;
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
            $sessionId = Session::valueForKey('ATHOS_SESSION_ID');

            $this->db->query('SELECT username FROM exm_users WHERE id=(SELECT user_id FROM exm_sessions WHERE id=?) AND is_active=true', $sessionId);
            return ucfirst($this->db->getRow()->username);
        }

        return 'Anonymous';
    }

    public function getUser(): mixed {
        if ($this->loggedIn) {
            $sessionId = Session::valueForKey('ATHOS_SESSION_ID');

            $this->db->query('SELECT * FROM exm_users WHERE id=(SELECT user_id FROM exm_sessions WHERE id=?) AND is_active=true', $sessionId);
            return $this->db->getRow();
        }

        return null;
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
            $sessionId = Session::valueForKey('ATHOS_SESSION_ID');

            $this->db->query('SELECT role FROM exm_users WHERE id=(SELECT user_id FROM exm_sessions WHERE id=?) AND is_active=true', $sessionId);
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
    * @see attemptCookieLogin()
    * @return bool true if a valid session is found.
    */
    private function attemptSessionLogin(): bool {
        if (Session::hasValueForKey('ATHOS_SESSION_ID')) {
            return $this->validateSession(Session::valueForKey('ATHOS_SESSION_ID'));
        }

        return false;
    }

    /**
    * Attempts to validate a user session by cookie
    *
    * @see attemptSessionLogin()
    * @return bool true if a valid session is found.
    */
    private function attemptCookieLogin(): bool {
        if (isset($_COOKIE['athos']) && is_string($_COOKIE['athos'])) {
            $s = json_decode($_COOKIE['athos'], true);

            if (isset($s['ATHOS_SESSION_ID'])) {
                return $this->validateSession($s['ATHOS_SESSION_ID']);
            }
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
        if($this->config->getEnvironmentVariable('use_email_login')) {
            $this->db->query('SELECT * FROM exm_users WHERE email=? AND password=? AND is_active=true', $username, hash('sha256', $password));
        } else {
            $this->db->query('SELECT * FROM exm_users WHERE username=? AND password=? AND is_active=true', $username, hash('sha256', $password));
        }

        if (!$this->db->hasRows()) {
            $this->loggedIn = false;
            return false;
        }

        $row = $this->db->getRow();

        $sessionId = md5($row->username . $row->password . time());

        if($this->config->get('db_provider') == 'pgsql') { 
            $this->db->query('INSERT INTO exm_sessions(id, user_id, expires_at) VALUES(?, ?, to_timestamp(?))', $sessionId, $row->id, time()+$this->ttl);
        } else {
            $this->db->query('INSERT INTO exm_sessions(id, user_id, expires_at) VALUES(?, ?, FROM_UNIXTIME(?))', $sessionId, $row->id, time()+$this->ttl);
        }
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
        $this->db->query('SELECT * FROM exm_sessions WHERE id=? AND expires_at > NOW() AND is_active=true', ...[$sessionId]);

        if ($this->db->hasRows()) {
            $this->db->query('UPDATE exm_sessions SET last_updated_at=NOW() WHERE id=?', $sessionId);
            Session::setValueForKey('ATHOS_SESSION_ID', $sessionId);
            $this->loggedIn = true;
        } else {
            $this->logout();
        }

        return $this->loggedIn;
    }

    /**
    * Stores the sessionID in a PHP session and cookie.
    *
    * @param string $sessionId User session ID
    */
    private function storeSessionData(string $sessionId): void {
        Session::setValueForKey('ATHOS_SESSION_ID', $sessionId);

        if ($this->shouldUseCookies()) {
            $s = json_encode(['ATHOS_SESSION_ID' => $sessionId]);
            setcookie('athos', $s, time()+$this->ttl);
        }
    }

    /**
    * Determines if cookies should be used.
    * Can be set using $config['use_cookies'].
    *
    * Default: true
    *
    * @return bool true if cookies should be used.
    */
    private function shouldUseCookies(): bool {
        if ($this->config->get('use_cookies') != null) {
            return $this->config->get('use_cookies');
        }

        return true;
    }
}
?>
