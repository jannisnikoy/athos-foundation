<?php

namespace Athos\Foundation;

/**
* Database
* Perform database queries using PDO.
*
* @package  athos-foundation
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-foundation
*/

class Database {
    private $db;
    private $host;
    private $user;
    private $pass;
    private $name;
    private $result;
    private $statement;

    /**
    * Initializes Database with the provided credentials.
    *
    * @param string $host
    * @param string $username
    * @param string $password
    * @param string $database
    */
    function __construct(string $host, string $username, string $password, string $database) {
        $this->db = false;
        $this->host = $host;
        $this->user = $username;
        $this->pass = $password;
        $this->name = $database;
    }

    /**
    * Executes a query and returns the result. Optional query binding is available by
    * using the ? placeholder for query parameters and passing them through with the
    * 'params' method attribute.
    *
    * @param string SQL query
    * @param array  Optional query parameters
    * @return array Results of the query
    */
    public function query(string $sql): array {
        if (!$this->isConnected()) $this->connect();

        $this->statement = $this->db->prepare($sql);
        $params = func_get_args();

        for ($i = 1; $i < count($params); $i++) {
            $this->statement->bindParam($i, $params[$i]);
        }

        $this->statement->execute();
        $this->result = $this->statement->fetchAll(\PDO::FETCH_OBJ);

        return $this->result;
    }

    /**
    * Determines if the executed query has any results.
    *
    * @return true if results are found.
    */
    public function hasRows(): bool {
        return (count($this->result) > 0);
    }

    /**
    * Retrieves all results found of the last executed query.
    *
    * @return array all results
    */
    public function getRows(): array {
        if (!$this->hasRows($this->result)) return array();

        return $this->result;
    }

    /**
    * Shortcut to retrieve a single row if only 1 result is expected.
    *
    * @return object First result found
    */
    public function getRow() {
        return $this->hasRows() ? $this->result[0] : false;
    }

    /**
    * Returns the insertId of the latest insertion.
    *
    * @return int latest insertId
    */
    public function insertId(): int {
        $id = $this->db->lastInsertId();

        if ($id === 0 || $id === false) {
            return false;
        } else {
            return $id;
        }
    }

    //
    // Private methods
    //

    /**
    * Attempts to connect to the database using PDO with the provided credentials.
    *
    * @return bool true if connection is succesful.
    */
    private function connect(): bool {
        $this->db = new \PDO("mysql:host=".$this->host.";dbname=".$this->name, $this->user, $this->pass);

        if (!$this->db) {
            throw new Error("An error occurred while connecting to the database: >>");
        }

        return $this->isConnected();
    }

    /**
    * Closes the database connection.
    */
    private function disconnect() {
        $this->db->close();
    }

    /**
    * Determines whether a database connection is active.
    *
    * @return true if a connection is present.
    */
    private function isConnected(): bool {
        return is_resource($this->db) && get_resource_type($this->db) == 'mysql link';
    }
}
?>
