<?php

class idDatabaseTestCase extends ezpDatabaseTestCase
{
  protected $backupGlobals = false;  
  
  /**
   * Sets up the database enviroment
   */
  protected function setUp()
  {
    if (ezpTestRunner::dbPerTest())
    {
      $dsn = ezpTestRunner::dsn();
      $this->sharedFixture = ezpTestDatabaseHelper::create($dsn);

      if ($this->insertDefaultData === true)
        ezpTestDatabaseHelper::insertDefaultData($this->sharedFixture);

      if (count($this->sqlFiles) > 0)
        ezpTestDatabaseHelper::insertSqlData($this->sharedFixture, $this->sqlFiles);
    }
    eZDB::setInstance($this->sharedFixture);
  }
    
  public function tearDown()
  {
    eZContentObject::clearCache();
    unset($GLOBALS['eZContentLanguageList']);
    unset($GLOBALS['eZContentLanguageMask']);
    unset($GLOBALS['eZContentClassAttributeCacheList']);
    unset($GLOBALS['eZContentClassAttributeCacheListFull']);
    unset($GLOBALS['eZContentClassObjectCache']);
    unset($GLOBALS['eZContentClassAttributeCache']);
    
    eZContentClassAttribute::resetClassAttributeIdentifierHash();
    
  }
}
