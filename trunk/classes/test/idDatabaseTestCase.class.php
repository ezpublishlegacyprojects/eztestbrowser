<?php
/**
* Copyright (C) 2009  Francesco trucchia
* 
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
* 
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.

* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*
* @author Francesco (cphp) Trucchia <ft@ideato.it>
* 
*/

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
      {
        ezpTestDatabaseHelper::insertDefaultData($this->sharedFixture);
      }

      if (count($this->sqlFiles) > 0)
      {
        if (!ezpTestDatabaseHelper::insertSqlData($this->sharedFixture, $this->sqlFiles))
        {
          throw new Exception('Some errors occurred loading sql files');
        }
      }
    }
    $this->sharedFixture = ezpDatabaseHelper::useDatabase(ezpTestRunner::dsn());
    eZDB::setInstance($this->sharedFixture);
  }
    
  public function tearDown()
  {
    parent::tearDown();
    eZContentObject::clearCache();
    unset($GLOBALS['eZContentLanguageList']);
    unset($GLOBALS['eZContentLanguageMask']);
    unset($GLOBALS['eZContentClassAttributeCacheList']);
    unset($GLOBALS['eZContentClassAttributeCacheListFull']);
    unset($GLOBALS['eZContentClassObjectCache']);
    unset($GLOBALS['eZContentClassAttributeCache']);
    
    eZContentClassAttribute::expireCache();
    
  }
}
