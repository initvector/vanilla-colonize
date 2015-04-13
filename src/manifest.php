<?php
/**
 * @author initvector
 * @license MIT
 */
namespace Initvector\Colonize;

/**
 * A class for loading data population criteria from a file, verifying
 * dependencies and executing the population routine
 */
class Manifest {

    /**
     * Parsed, validated manifest data
     * @var
     */
    private static $manifest = false;

    /**
     * Valid tables and their dependencies, in the order they should be run
     * @var array
     */
    private static $validTables = array(
        'User' => array(),
        'Category' => array(),
        'Discussion' => array('Category'),
        'Comment' => array('Discussion')
    );

    /**
     * Load data from a manifest JSON file, validate it and possibly run it
     *
     * @param string $filename Name of the manifest JSON file
     * @param bool $runAfterLoad Perform population after loading data?
     * @return array Parsed manifest data
     */
    public static function load($filename = 'manifest.json', $runAfterLoad = false) {
        // Does the manifest even exist?
        if (!file_exists($filename)) {
            throw new \ErrorException("Unable to find manifest: $filename");
        }

        // Can we read the file?
        if (!is_readable($filename)) {
            throw new \ErrorException("Unable to read manifest: $filename");
        }

        // Can we read the file enough to pull its contents?
        if (!$manifest_json = file_get_contents($filename)) {
            throw new \ErrorException("An error occurred while reading manifest: $filename");
        }

        // Attempt to convert the manifest contents to a PHP assoc array
        $manifest = json_decode($manifest_json, true);

        // Anything to work with?
        if (empty($manifest)) {
            throw new \ErrorException("Invalid contents of manifest: $filename");
        }

        // Only keep the tables we know we can work with
        $tablesToProcess = array_intersect_key($manifest, self::$validTables);

        // Anything left after we've filtered out the invalid data?
        if (empty($tablesToProcess)) {
            throw new \ErrorException("No valid tables found in manifest: $filename");
        }

        // Verify dependencies
        foreach ($manifest as $tableName => $targetRows) {
            $dependencies = self::$validTables[$tableName];

            // Any depdencies for this table to consider?
            if (count($dependencies)) {
                /**
                 * Iterate through our dependencies and make sure they'll be
                 * taken care of.  If they won't be, throw an exception.
                 */
                foreach ($dependencies as $currentDependency) {
                    if (!array_key_exists($currentDependency, $manifest)) {
                        throw new \ErrorException("Unmet dependency: $currentDependency");
                    }
                }
            }
        }

        // Stash our verified data
        self::$manifest = $manifest;

        /**
         * Should we just go ahead with population operations now that we have
         * our data?
         */
        if ($runAfterLoad) {
            self::run();
        }

        return $manifest;
    }

    /**
     * Execute the operations necessary to populate the DB with our data
     */
    public static function run() {
        if (!self::$manifest) {
            throw new \ErrorException("No valid manifest to execute");
        }

        foreach (self::$validTables as $currentTable => $dependencies) {
            if (array_key_exists($currentTable, self::$manifest)) {
                $tableClass = "Initvector\\Colonize\\Table\\$currentTable";
                $targetRows = self::$manifest[$currentTable];

                if (!class_exists($tableClass)) {
                    throw new \ErrorException("Unable to find class: $tableClass");
                }

                $instance = $tableClass::getInstance();
                $instance->generate($targetRows);
            }
        }
    }
}
