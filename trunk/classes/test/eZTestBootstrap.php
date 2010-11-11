<?php

set_time_limit(0);

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'tests/toolkit/ezptestrunner.php';

class eZTestBootstrap
{
  private function excludeTestSystemFromCodeCoverageReports()
  {
    PHPUnit_Util_Filter::addDirectoryToFilter( getcwd() . '/tests' );
  }

  private function whitelistAlleZPublishKernelFiles()
  {
    $baseDir = getcwd();
    $autoloadArray = include 'autoload/ezp_kernel.php';
    foreach ($autoloadArray as $class => $file)
    {
      if (strpos($file, 'tests') !== 0)
      {
        PHPUnit_Util_Filter::addFileToWhitelist("{$baseDir}/{$file}");
      }
    }
  }

  public function run()
  {
    $this->excludeTestSystemFromCodeCoverageReports();
    $this->whitelistAlleZPublishKernelFiles();

    eZExecution::setCleanExit();
  }
}

