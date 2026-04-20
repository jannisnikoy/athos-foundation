<?php

namespace Athos\Foundation;
use \Firebase\JWT\JWT;
use \Jchook\Uuid;

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

    function __construct() {
        global $config, $db;

        $this->config = $config;
        $this->db = $db;

        $this->attemptCookieLogin();
    }


    /**
    * Checks if a user account exists and registers if not.
    *
    * @param string $username
    * @param string $password
    * @return bool true if registration was succesful
    */
    public function register(string $username, string $password): bool {
        $this->db->query('SELECT * FROM {prefix}users WHERE username=? OR email=?', $username, $username);

        if ($this->db->hasRows()) {
            return false;
        }

        $this->db->query('INSERT INTO {prefix}users (username, password) VALUES(?, ?)', $username, password_hash($password, PASSWORD_ARGON2ID));

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

        $host = $this->config->getEnvironmentVariable('jwt_host') ?? $_SERVER['HTTP_HOST'];
        $host = str_replace('http://', '', $host);
        $host = str_replace('https://', '', $host);
        $host = explode(':', $host)[0];
        
        setcookie(
            'athos',
            '',
            [
                'expires'  => time() + ($this->config->getEnvironmentVariable('jwt_expiration_time') ?? 3600),
                'path'     => '/',
                'domain'   => $host,
                'secure'   => $this->config->getEnvironmentVariable('jwt_require_secure', true),
                'httponly' => $this->config->getEnvironmentVariable('jwt_require_secure', true),
                'samesite' => $this->config->getEnvironmentVariable('jwt_require_secure', true) == true ? 'Strict' : 'None',
            ]
        );

        setcookie(
            'athos-refresh',
            '',
            [
                'expires'  => time() + ($this->config->getEnvironmentVariable('jwt_expiration_time') ?? 3600),
                'path'     => '/',
                'domain'   => $host,
                'secure'   => $this->config->getEnvironmentVariable('jwt_require_secure', true),
                'httponly' => $this->config->getEnvironmentVariable('jwt_require_secure', true),
                'samesite' => $this->config->getEnvironmentVariable('jwt_require_secure', true) == true ? 'Strict' : 'None',
            ]
        );
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

        $this->loggedIn = $this->attemptCookieLogin();
        return $this->loggedIn;
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
            $user = $this->checkToken();

            $this->db->query('SELECT username FROM {prefix}users WHERE id=? AND is_active=true', $user->userId);
            return ucfirst($this->db->getRow()->username);
        }

        return 'Anonymous';
    }

    public function getUser(): mixed {
        if ($this->loggedIn) {
            $user = $this->checkToken();

            $this->db->query('SELECT * FROM {prefix}users WHERE id=? AND is_active=true AND role=?', $user->userId, $user->role);
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
            $user = $this->checkToken();
            
            return $user->role;
        }

        return 'none';
    }


    /**
     * Generates a JWT token for a user
     *
     * @param string $userId The user ID
     * @return string The JWT token
     */
    public function getJwtToken(string $userId, string $role = 'client', string $aud = 'dashboard'): string {
        $arClaim['iss'] = $this->config->getEnvironmentVariable('jwt_host') ?? $_SERVER['HTTP_HOST'];
        $arClaim['iat'] = time();
        $arClaim['exp'] = time() + ($this->config->getEnvironmentVariable('jwt_expiration_time') ?? 3600);
        $arClaim['sid'] = Uuid::v4();
        $arClaim['sub'] = $userId;
        $arClaim['aud'] = $aud;
        $arClaim['role'] = $role;

        $key = file_get_contents($this->config->getEnvironmentVariable('jwt_private_key'));

        return JWT::encode($arClaim, $key, 'RS256');
      }

    /**
     * Checks if the token is valid
     *
     * @return object|null The decoded token or null if the token is invalid
    */
    public function checkToken(bool $allowInvalidated = false) {
        if(isset($_COOKIE['athos'])) {
            $aud = 'dashboard';
            $jwtToken = $_COOKIE['athos'];
        } else {
            $headers = array_change_key_case(getallheaders());

            if(preg_match('/Bearer\s(\S+)/', $headers['authorization'], $matches)) {
                $jwtToken = $matches[1];
                $aud = 'api';
            }
        }

        if(!isset($jwtToken)) {
            return null;
        }

        try {
            $decoded = JWT::decode($jwtToken, new \Firebase\JWT\Key(file_get_contents($this->config->getEnvironmentVariable('jwt_public_key')), 'RS256'));

            if($decoded->aud == $aud && $decoded->iss == ($this->config->getEnvironmentVariable('jwt_host') ?? $_SERVER['HTTP_HOST']) && $decoded->exp > time()) {
                $decoded->userId = $decoded->sub;
                return $decoded;
            }
        } catch (\Firebase\JWT\ExpiredException $e) {
            if($allowInvalidated) {
                $parts = explode('.', $jwtToken);
                if (count($parts) !== 3) return null;
                
                $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')));
                $payload->userId = $payload->sub;
                return $payload;
            }
            return null;
        }

        return null;
    }

    //
    // Private methods
    //

    /**
    * Attempts to validate a user session by cookie
    *
    * @see attemptSessionLogin()
    * @return bool true if a valid session is found.
    */
    private function attemptCookieLogin(): bool {
        if (isset($_COOKIE['athos'])) {
            return null !== $this->checkToken();
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
        $encryptedPassword = password_hash($password, PASSWORD_ARGON2ID);

        if($this->config->getEnvironmentVariable('use_email_login')) {
            $this->db->query('SELECT id, email, password, role FROM {prefix}users WHERE email=? AND is_active=true', $username);
        } else {
            $this->db->query('SELECT id, email, password, role FROM {prefix}users WHERE username=? AND is_active=true', $username);
        }

        if (!$this->db->hasRows()) {
            $this->loggedIn = false;
            return false;
        }

        $row = $this->db->getRow();

        if (!password_verify($password, $row->password)) {
            $this->loggedIn = false;
            return false;
        }

        $token = $this->getJwtToken($row->id, $row->role);
        $refreshToken = bin2hex(random_bytes(64));

        $this->storeSessionData($token, $refreshToken);

        $decodedToken = JWT::decode($token, new \Firebase\JWT\Key(file_get_contents($this->config->getEnvironmentVariable('jwt_public_key')), 'RS256'));

        if($this->config->get('db_provider') == 'pgsql') { 
            $this->db->query('INSERT INTO {prefix}sessions(id, user_id, aud, refresh_token, expires_at, user_agent, ip_address) VALUES(?, ?, ?, ?, to_timestamp(?), ?, ?)', $decodedToken->sid, $row->id, $decodedToken->aud, $refreshToken, $decodedToken->exp,  $_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']);
        } else {
            $this->db->query('INSERT INTO {prefix}sessions(id, user_id, aud, refresh_token, expires_at, user_agent, ip_address) VALUES(?, ?, ?, ?, FROM_UNIXTIME(?), ?, ?)', $decodedToken->sid, $row->id, $decodedToken->aud, $refreshToken, $decodedToken->exp,  $_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR']);
        }

        $this->loggedIn = true;

        return true;
    }

    /**
    * Stores the sessionID in a PHP session and cookie.
    *
    * @param string $sessionId User session ID
    */
    private function storeSessionData(string $jwt, string $refreshToken): void {
        $host = $this->config->getEnvironmentVariable('jwt_host') ?? $_SERVER['HTTP_HOST'];
        $host = str_replace('http://', '', $host);
        $host = str_replace('https://', '', $host);
        $host = explode(':', $host)[0];
        
        setcookie(
            'athos',
            $jwt,
            [
                'expires'  => time() + ($this->config->getEnvironmentVariable('jwt_expiration_time') ?? 3600), // 1 hour
                'path'     => '/',
                'domain'   => $host,
                'secure'   => $this->config->getEnvironmentVariable('jwt_require_secure', true),
                'httponly' => $this->config->getEnvironmentVariable('jwt_require_secure', true),
                'samesite' => $this->config->getEnvironmentVariable('jwt_require_secure', true) == true ? 'Strict' : 'None',
            ]
        );

        setcookie(
            'athos-refresh',
            $refreshToken,
            [
                'expires'  => time() + ($this->config->getEnvironmentVariable('jwt_expiration_time') ?? 30758400), // 1 year
                'path'     => '/',
                'domain'   => $host,
                'secure'   => $this->config->getEnvironmentVariable('jwt_require_secure', true),
                'httponly' => $this->config->getEnvironmentVariable('jwt_require_secure', true),
                'samesite' => $this->config->getEnvironmentVariable('jwt_require_secure', true) == true ? 'Strict' : 'None',
            ]
        );
    }
}
?>
