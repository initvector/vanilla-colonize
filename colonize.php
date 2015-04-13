<?php
// Grab the startup time.  We'll need it for tracking total time.
define('APP_START', microtime(true));

// Make sure we see everything that might be going awry.
ini_set('display_errors',1);
error_reporting(E_ALL);

// Load up Composer's autoloader.
require 'vendor/autoload.php';

// Initialize CLI interface and configure the options available.
$cli = new Garden\Cli\Cli();
$cli->description('Populate an empty Vanilla forum with fake data')
    ->opt('host:h', 'Connect to host.', true)
    ->opt('port:P', 'Port number to use.', false, 'integer')
    ->opt('user:u', 'User for login if not current user.', true)
    ->opt('password:p', 'Password to use when connecting to server.')
    ->opt('database:d', 'The name of the database to dump.', true)
    ->opt('manifest:m', 'File locaion of the manifest file.');

// Parse the commandline options.
$args = $cli->parse($argv, true);

// Connect to our target database
$db = Initvector\Colonize\Database::connect(
    $args->getOpt('host'),
    $args->getOpt('user'),
    $args->getOpt('password', ''),
    $args->getOpt('database'),
    $args->getOpt('port', 3306)
);

$manifest = $args->getOpt('manifest', 'manifest.json');

Initvector\Colonize\Manifest::load($manifest, true);

//@todo Could really use some dba/counts goodness here.

// And we're done.  How'd we do?
echo "\nAll operations completed in " . number_format(microtime(true) - APP_START, 2) . "s\n";
