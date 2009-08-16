<?php
/**
 * File containing the eZContentObjectRegression class
 *
 * @copyright Copyright (C) 1999-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 * @package tests
 */

class idObjectRepositoryTest extends ezpDatabaseTestCase
{
  protected $backupGlobals = false;

  public function __construct()
  {
    parent::__construct();
    $this->setName("eZContentObject ideato Regression Tests");
  }

  public function testRetrieveObject()
  {
    $folder = new idObject("folder", 2);
    $folder->name = __FUNCTION__;
    $folder->short_description = "123";
    $folder->publish();

    $object = idObjectRepository::retrieveById($folder->id);
    $this->assertTrue($object instanceof idObject);
    $this->assertTrue($object->id == $folder->id);
    $this->assertTrue($object->name == $folder->name);

    $object = idObjectRepository::retrieveByNodeId($folder->main_node_id);
    $this->assertTrue($object instanceof idObject);
    $this->assertTrue($object->id == $folder->id);
    $this->assertTrue($object->name == $folder->name);
  }
}

?>