<?php
/**
 * File containing the eZKernelTestSuite class
 *
 * @copyright Copyright (C) 2009 eFrancesco Trucchia
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 * @package tests
 */

class eZTestBrowserTestSuite extends ezpTestSuite
{
  public function __construct()
  {
    parent::__construct();
    $this->setName('eZ Test Browser Test Suite');
    
    $this->addTestSuite('ezpYamlDataTest');
    $this->addTestSuite('ezpLoaderExceptionTest');
    $this->addTestSuite('ezpLoaderTest');
    $this->addTestSuite('ezpControllerTest');
    
    $this->addTestSuite('idObjectRepositoryTest');
    $this->addTestSuite('idClassRepositoryTest');
    $this->addTestSuite('idObjectTest');    
    $this->addTestSuite('idAttributeTest');

    $this->addTestSuite('idTestRunnerTest');
    $this->addTestSuite('ezpYamlDataBenchmarkTest');
    $this->addTestSuite('ParseArgumentsTest');

    $this->addTestSuite('eZBrowserTestCaseTest');
    $this->addTestSuite('ExporterYamlClassTest');

    $this->addTestSuite('HarmonyBugsWithItaLoaderTest');
  }
  
  public static function suite()
  {
    return new self();
  }
}

?>
