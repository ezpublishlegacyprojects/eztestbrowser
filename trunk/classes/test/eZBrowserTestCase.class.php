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
  protected $kernel_schema = 'kernel/sql/mysql/kernel_schema.sql';
  protected $cleandata = 'kernel/sql/mysql/cleandata.sql';
  protected $fixtures_classes = null;
  protected $fixtures_objects = null;
  protected $verbose = false;
  protected $load_database = true;

  protected static $load_once = false;
  protected static $fixtures_hash;
  
  abstract protected function fixturesSetUp();

  protected function insertSql()
  {
    $this->sharedFixture = ezpTestDatabaseHelper::create(ezpTestRunner::dsn());

    if (!ezpTestDatabaseHelper::insertSqlData( $this->sharedFixture, $this->sqlFiles ))
    {
      throw new Exception('Impossible to load some sql files');
    }

    eZDB::setInstance( $this->sharedFixture );
  }

  protected function initialize()
  {
    eZCache::clearAll();
    
    $GLOBALS['eZTextCodecInternalCharsetReal'] = 'utf-8';
    $this->charset = $GLOBALS['eZTextCodecInternalCharsetReal'];

    $this->fixtures_hash = $this->getFixturesHash();
    $this->load_once = true;

    $this->sqlFiles[] = realpath($this->kernel_schema);
    $this->sqlFiles[] = realpath($this->cleandata);
  }
  
  private function getFixturesHash()
  {
    return hash('md5', $this->fixtures_classes.$this->fixtures_objects);
  }

  protected function checkLoadDatabase()
  {
    return (bool)($this->load_database && (!self::$load_once || self::$fixtures_hash != $this->getFixturesHash()));
  }
  
  /**
   * Sets up the database enviroment
   */
  protected function setUp()
  {
    $this->fixturesSetUp();
    
    if($this->checkLoadDatabase())
    {
      $this->initialize();
      $this->insertSql();
  
      $data = new ezpYamlData($this->verbose);
      $data->loadClassesData(realpath($this->fixtures_classes));
      $data->loadObjectsData(realpath($this->fixtures_objects));
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

  /**
   * Remove an eZContentObject
   *
   * @param int $object_id
   */
  protected function removeObject($object_id)
  {
    $object = eZContentObject::fetch($object_id);
    $object->remove();
  }

  /**
   * Add an eZContentObject and returns the object id
   *
   * @param string $class_identifier
   * @param int $parent_node_id
   * @param array $attributes
   * @return int
   */
  protected function addObject($class_identifier, $parent_node_id = 2, $attributes = array())
  {
    $object = new idObject($class_identifier, $parent_node_id);
    $object->fromArray($attributes);
    $object->store();
    $object->publish();

    return $object;
  }

}
