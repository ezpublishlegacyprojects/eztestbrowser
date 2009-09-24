#!/usr/bin/env php
<?php
/**
 * File containing the runtests CLI script
 *
 * @copyright Copyright (C) 1999-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 * @package tests
 */

set_time_limit( 0 );

require_once 'autoload.php';

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'tests/toolkit/ezptestrunner.php';

// Exclude the test system from code coverage reports
PHPUnit_Util_Filter::addDirectoryToFilter( getcwd() . '/tests' );

// Whitelist all eZ Publish kernel files
$baseDir = getcwd();
$autoloadArray = include 'autoload/ezp_kernel.php';
foreach ( $autoloadArray as $class => $file )
{
    // Exclude files from the tests directory
    if ( strpos( $file, 'tests' ) !== 0 )
    {
        PHPUnit_Util_Filter::addFileToWhitelist( "{$baseDir}/{$file}" );
    }
}


eZExecution::setCleanExit();
ezpTestRunner::main();

?>