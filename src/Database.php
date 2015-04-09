<?php
/**
 * @author initvector
 * @license MIT
 */
namespace Initvector\Colonize;

/**
 * A basic singleton for mysqli.
 */
class Database {
    /**
     * A reference to the mysqli instance.
     * @var mysqli
     */
    private static $instance;

    /**
     * Connect to the MySQL database and stash a reference to the connection.
     *
     * @param string $host Can be either a host name or an IP address.
     * @param string $user The MySQL user name.
     * @param string $password The MySQL password associated with the user name.
     * @param string $database Default database to use when performing queries.
     * @param integer $port Specifies the port to connect to the MySQL server.
     * @return mysqli An object which represents the connection.
     */
    public static function connect($host, $user, $password, $database, $port) {
        // Attempt a connection
        $dbConn = new \mysqli($host, $user, $password, $database, $port);

        // Any errors?
        if ($dbConn->connect_errno) {
            throw new ErrorException("MySQL connection error ({$dbConn->connect_errno}) {$dbConn->connect_error}\n");
        } else {
            // If no errors were encountered, save a reference to the object.
            self::$instance = $dbConn;
        }

        return $dbConn;
    }

    /**
     * Grab object representing our connection to the current MySQL connection.
     *
     * @return mysqli An object which represents the connection.
     */
    public static function getInstance() {
        if (!self::$instance instanceof \mysqli) {
            throw new ErrorException('No valid MySQL connection available');
        } else {
            return self::$instance;
        }
    }
}
