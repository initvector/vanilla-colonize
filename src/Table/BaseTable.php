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
     * Name of the ID field in the associated table
     * @var string
     */
    protected $idField = '';

    /**
     * IDs of rows that have been inserted.
     * @var array
     */
    protected $ids = array();

    /**
     * Values for rows to be inserted in bulk.
     * @var array
     */
    protected $rowsToInsert = array();

    /**
     * Number of rows per insert statement.
     * @var integer
     */
    protected $rowsPerInsert = 100;

    /**
     * Name of the table associated with the object
     * @var string
     */
    protected $tableName = '';

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

        // Magically assign idField and tableName properties, if empty
        $reflection = new \ReflectionClass($this);

        if ($this->idField == '') {
            $this->idField = $reflection->getShortName() . 'ID';
        }
        if ($this->tableName == '') {
            $this->tableName = 'GDN_' . $reflection->getShortName();
        }
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
        // Are we tracking row IDs?
        if ($this->trackRows) {
            /**
             * Since we're using multi-row inserts, inserted IDs aren't so easy
             * to grab as they're being inserted.  We'll just grab them after
             * everything has been inserted.
             */
            $lookupQuery = "select {$this->idField} from {$this->tableName}";
            $lookupResult = $this->db->query($lookupQuery);
            echo "\nGathering IDs...\n";
            if ($lookupResult instanceof \mysqli_result) {
                while ($currentID = $lookupResult->fetch_array()) {
                    $this->ids[] = $currentID[0];
                }
            } else {
                throw new \ErrorException("Unable to retrieve IDs: " . $this->db->error);
            }
        }
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
     * Flush the queue and insert all currently accumulated records, if queue
     * meets or exceeds specified batch size.
     *
     * @param bool $forceFlush Ignore the batch size and flush, regardless
     */
    protected function flushInserts($forceFlush = false) {
        // Are we under the batch and not forcing a flush?
        if (count($this->rowsToInsert) < $this->rowsPerInsert && !$forceFlush) {
            return false;
        }

        // Is there anything to insert?
        if (!empty($this->rowsToInsert)) {
            /**
             * Each row's fields should be prepared before insertion for the sake
             * of sanitizing, replacing special value placeholders, etc.
             */
            array_walk_recursive($this->rowsToInsert, array($this, 'prepareField'));

            // Build out the values for our multi-row insert
            $rowValues = '';
            foreach ($this->rowsToInsert as $currentRow) {
                $rowValues .= "(" . implode(',', $currentRow) . "),";
            }

            // Clip off that dangling comma
            $rowValues = rtrim($rowValues, ',');

            // Replace placeholders in the statement
            $insertRows = str_replace(
                array(
                    ':table:',
                    ':values:',
                ),
                array(
                    $this->tableName,
                    $rowValues
                ),
                $this->insertStatement
            );

            // Attempt an insert and flush our queue
            if (!$this->db->query($insertRows)) {
                throw new \ErrorException("Failure to insert: " . $this->db->error);
            }

            $this->rowsToInsert = array();

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
            /**
             * Since we aren't forcing a batch, flushInserts will only trigger
             * a multi-row insert if the current records meet or exceed the
             * specified batch.
             */
            $this->flushInserts();
            echo "\r\033[KProcessed $generated/$rows";
        }

        /**
         * $generated gets incremented once more at the end of the last loop
         * iteration.  Adjust, accounting for that.
         */
        $generated--;

        // Force a flush, in case we had anything still hung up in the queue.
        $this->flushInserts(true);

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
     * Prepare field values for insertion
     *
     * @param $value Field value for row
     * @param string $key Field name
     */
    protected function prepareField(&$value, $key) {
        switch ($value) {
            // Grab a random date from within the past year
            case ':randomPastDate:':
                $value = '(select now() - interval floor(rand() * 365) day)';
                break;
            // Just escape the value
            default:
                $value = "'" . $this->db->real_escape_string($value) . "'";
        }
    }
}
