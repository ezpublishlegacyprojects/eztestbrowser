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

abstract class eZBrowserTestCase extends PHPUnit_Extensions_WebBrowserTestCase
{
  protected $sqlFiles = array();

  protected $backupGlobals = false;

  protected static $load_once = false;
  
  protected static $fixtures_hash;
  
  protected $kernel_schema = 'kernel/sql/mysql/kernel_schema.sql';
  
  protected $cleandata = 'kernel/sql/mysql/cleandata.sql';
  
  protected $fixtures_classes = null;
  
  protected $fixtures_objects = null;
  
  protected function initialize()
  {
    $this->sqlFiles = array(realpath($this->kernel_schema), realpath($this->cleandata));
  }
  
  private function getFixturesHash()
  {
    return hash('md5', $this->fixtures_classes.$this->fixtures_objects);
  }
  
  /**
   * Sets up the database enviroment
   */
  protected function setUp()
  {
    if(!self::$load_once || self::$fixtures_hash != $this->getFixturesHash())
    {
      self::$fixtures_hash = $this->getFixturesHash();
      self::$load_once = true;
      
      $this->initialize();

      $dsn = ezpTestRunner::dsn();
      $this->sharedFixture = ezpTestDatabaseHelper::create($dsn);
      
      if (!ezpTestDatabaseHelper::insertSqlData( $this->sharedFixture, $this->sqlFiles ))
      {
        throw new Exception('Impossible to load some sql files');
      };
      
      eZDB::setInstance( $this->sharedFixture );

      if (!$this->fixtures_classes || !$this->fixtures_objects)
      {
        throw new Exception('The fixtures files is not configured');
      }

      $classes_fixtures = realpath($this->fixtures_classes);
      $objects_fixtures = realpath($this->fixtures_objects);

      if (!file_exists($classes_fixtures))
      {
        throw new Exception("The classes fixtures file $classes_fixtures does not exists");
      }

      if (!file_exists($objects_fixtures))
      {
        throw new Exception("The objects fixtures file $objects_fixtures does not exists");
      }

      $data = new ezpYamlData();
      $data->loadClassesData($classes_fixtures);
      $data->loadObjectsData($objects_fixtures);

      $this->charset = $GLOBALS['eZTextCodecInternalCharsetReal'];
      $GLOBALS['eZTextCodecInternalCharsetReal'] = 'utf-8';
    }
    eZDB::setInstance($this->sharedFixture);
  }

  public function listUsedTemplates()
  {
    $nodes = $this->domCssSelector->matchAll('table#templateusage tr td');
    $values = $nodes->getValues();

    for ($i = 0; $i <= count($values); $i++)
    {
        if ($i % 6 == 0) echo "\n";
        echo $values[$i]."\t";
    }
  }

  public function tearDown()
  {
    unset($GLOBALS['eZContentLanguageList']);
    unset($GLOBALS['eZContentLanguageMask']);

  }
}