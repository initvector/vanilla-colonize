<?php
namespace Initvector\Colonize\Table;
use InitVector\Colonize\Database;

abstract class BaseTable {
    /**
     * Reference to our MySQL connection.
     * @var mysqli
     */
    protected $db;

    /**
     * A reference to our object instance.
     * @var BaseTable
     */
    protected static $instance;

    /**
     * Format of the prepared statement.
     * @var string
     */
    protected $insertStatement;

    /**
     * Holds reference to mysqli_stmt representing prepared $insertStatement
     * @var
     */
    protected $preparedInsertStatement;

    /**
     * Type mapping of placeholders in the prepared statement.
     * @var string
     */
    protected $insertPlaceholders;

    /**
     * IDs of rows that have been inserted.
     * @var array
     */
    protected $ids = array();

    /**
     * Keep track of inserted rows by ID
     */
    protected $trackRows = true;

    /**
     * Class constructor.
     */
    public function __construct() {
        // Grab and save a reference to the database connection.
        $this->db = Database::getInstance();
    }

    /**
     * Defines the process for adding a new row to the current table type.
     */
    abstract protected function addRow();

    /**
     * Run after fake content is generated.  Intended to allow child classes
     * to perform followup actions.
     */
    protected function afterGenerate() {
    }

    /**
     * Run before fake content is generated.  Intended to allow checks and
     * preparations.
     */
    protected function beforeGenerate() {
        // Just making sure we actually have a database connection.
        if (!$this->db instanceof \mysqli) {
            throw new \ErrorException('No valid MySQL connection available');
        }
    }

    /**
     * Iterate a specified number of times, generatinging rows of the current
     * content type.
     *
     * @param integer $rows The number of items to insert.
     */
    public function generate($rows = 1) {
        // Grab the start time for timing purposes
        $start = microtime(true);

        // MySQL connection alias
        $this->beforeGenerate();

        /**
         * These session variables are recommended for bulk data loading.
         * https://dev.mysql.com/doc/refman/5.5/en/optimizing-innodb-bulk-data-loading.html
         */
        $db = $this->db;
        $db->query("set @@session.autocommit=0");
        $db->query("set @@session.unique_checks=0");
        $db->query("set @@session.foreign_key_checks=0");

        for ($generated = 1; $generated <= $rows; $generated++) {
            $this->addRow();
            echo "\r\033[KProcessed $generated/$rows";
        }

        // Commits all of our generated row inserts
        $db->query("commit");

        $this->afterGenerate();

        /**
         * Calculate and output the total time and average records-per-second
         * for this table.
         */
        $duration = microtime(true) - $start;
        $durationFormatted = number_format($duration, 2);

        $rps = $duration > 0 ? ($generated / $duration) : $generated;
        $rpsFormatted = number_format($rps, 2);

        echo "\nCompleted content generation in {$durationFormatted}s (avg. {$rpsFormatted}rps)\n";
    }

    /**
     * Return instance of current object.  Used for singleton design.
     *
     * @return BaseTable Instance of current object.
     */
    public static function getInstance() {
        // Grab the name of the child class the call is performed against.
        $class = get_called_class();

        // Is the value we have an instance of our child class?
        if (!static::$instance instanceof $class) {
            // If not, making it an instance of our child class.
            static::$instance = new $class;
        }

        return static::$instance;
    }

    /**
     * Grab a prepared statement object representing our statement template
     *
     * @return bool|mysqli_stmt Statement object on success, false on failure
     */
    public function getPreparedInsertStatement() {
        // Already have a statement object? Great! Return it.
        if ($this->preparedInsertStatement instanceof mysqli_stmt) {
            return $this->preparedInsertStatement;
        }

        // MySQL connection alias
        $db = $this->db;

        /**
         * Each child class should have an insertStatement set that defines
         * the prepared statement format for their inserts.  Use that to
         * create our prepared statement here.
         */
        $preparedStatement = $db->prepare($this->insertStatement);

        // Any errors so far?
        if ($db->errno) {
            throw new \ErrorException($db->error);
            return false;
        }

        return $this->preparedInsertStatement = $preparedStatement;
    }

    /**
     * Grab a random ID from the object's array of row IDs
     *
     * @return integer An ID representing an inserted row
     */
    public function getRandomId() {
        if (empty($this->ids)) {
            throw new \ErrorException("No IDs found (" . get_called_class() . ")");
        }

        $totalRows = count($this->ids);
        $randomIndex = mt_rand(0, $totalRows - 1);
        return $this->ids[$randomIndex];
    }

    /**
     * Create and execute a prepared insert statement.
     *
     * @param array @parameters Data to populate the prepared statement.
     * @return bool True on success, false on failure.
     */
    protected function prepareAndInsert($parameters) {
        // Simple alias for the sake of brevity.
        $db = $this->db;

        // Is our one and only parameter not even an array?
        if (!is_array($parameters)) {
            return false;
        }

        /**
         * Each child class should have an insertStatement set that defines
         * the prepared statement format for their inserts.  Use that to
         * create our prepared statement here.
         */
        $statement = $this->getPreparedInsertStatement();

        /**
         * Here's where it starts to get a little hacky.  bind_param isn't so
         * great with dynamic numbers of prepared statement variables.  We have
         * to use call_user_func to make that happen.  Our first parameter to
         * bind_param has to be a type mapping of the supplied variables, so
         * that needs to be popped on, first.
         */

        array_unshift($parameters, $this->insertPlaceholders);

        /**
         * bind_param doesn't play well with values.  It requires references.
         * That is where this...thing comes into play.  We just create a new
         * array with references to the old array.
         */
        $parameters_ref = array();
        foreach ($parameters as &$current_parameter) {
            $parameters_ref[] = &$current_parameter;
        }

        /**
         * After the two kludges, we can call bind_param with a dynamic number
         * of variables.
         */
        call_user_func_array(array($statement, 'bind_param'), $parameters_ref);

        // After all that, did we run into an error?
        if (!$statement->execute()) {
            throw new \ErrorException($statement->error);
            return false;
        }

        // Push the latest row ID onto the current type's ID array, if tracking.
        if ($this->trackRows) {
            $this->ids[] = $db->insert_id;
        }

        return true;
    }
}
