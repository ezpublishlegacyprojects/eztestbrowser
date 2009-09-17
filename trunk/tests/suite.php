<?php
/**
 * File containing the eZKernelTestSuite class
 *
 * @copyright Copyright (C) 2009 eFrancesco Trucchia
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 * @package tests
 */

class ideatoTestSuite extends ezpDatabaseTestSuite
{
  public function __construct()
  {
    parent::__construct();
    $this->setName('Ideato Test Suite');
    
    $this->addTestSuite('ezpYamlDataTest');
    $this->addTestSuite('idObjectRepositoryTest');
    $this->addTestSuite('FunctionalTest');
    $this->addTestSuite('idObjectTest');
  }
  
  public static function suite()
  {
    return new self();
  }
}

?>
