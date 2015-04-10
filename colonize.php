<?php
// Grab the startup time.  We'll need it for tracking total time.
define('APP_START', microtime(true));

// Make sure we see everything that might be going awry.
ini_set('display_errors',1);
error_reporting(E_ALL);

// Load up Composer's autoloader.
require 'vendor/autoload.php';

// Values are hardcoded....for now.
// @todo Make these configurable with commandline options.
$categories = 10;
$comments = 10000;
$discussions = 1000;
$users = 100;

// Initialize CLI interface and configure the options available.
$cli = new Garden\Cli\Cli();
$cli->description('Populate an empty Vanilla forum with fake data')
    ->opt('host:h', 'Connect to host.', true)
    ->opt('port:P', 'Port number to use.', false, 'integer')
    ->opt('user:u', 'User for login if not current user.', true)
    ->opt('password:p', 'Password to use when connecting to server.')
    ->opt('database:d', 'The name of the database to dump.', true);

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

// Generate dummy data for our supported tables.
// @todo Make these configurable with commandline options.
Initvector\Colonize\Table\User::getInstance()->generate($users);
Initvector\Colonize\Table\Category::getInstance()->generate($categories);
Initvector\Colonize\Table\Discussion::getInstance()->generate($discussions);
Initvector\Colonize\Table\Comment::getInstance()->generate($comments);

//@todo Could really use some dba/counts goodness here.

// And we're done.  How'd we do?
echo "\nAll operations completed in " . number_format(microtime(true) - APP_START, 2) . "s\n";
