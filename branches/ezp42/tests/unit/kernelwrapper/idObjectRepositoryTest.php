<?php
/**
 * File containing the eZContentObjectRegression class
 *
 * @copyright Copyright (C) 1999-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 * @package tests
 */

class idObjectRepositoryTest extends idDatabaseTestCase
{
  protected $backupGlobals = false;

  private function buildFolder($name, $remote_id = 'folder')
  {
    $folder = new idObject("folder", 2);
    $folder->name = $name;
    $folder->short_description = "123";
    $folder->setAttribute('remote_id', $remote_id);
    $folder->store();
    $folder->publish();

    return $folder;
  }

  public function __construct()
  {
    parent::__construct();
    $this->setName("eZContentObject ideato Regression Tests");
  }

  public function testRetrieveObject()
  {
    $folder = $this->buildFolder(__FUNCTION__);

    $object = idObjectRepository::retrieveById($folder->id);

    $this->assertTrue($object instanceof idObject);
    $this->assertTrue($object->id == $folder->id);
    $this->assertEquals((string)$object->name, __FUNCTION__);

    $object = idObjectRepository::retrieveByNodeId($folder->main_node_id);

    $this->assertTrue($object instanceof idObject);
    $this->assertTrue($object->id == $folder->id);
    $this->assertEquals((string)$object->name, __FUNCTION__);

    $object = idObjectRepository::retrieveByRemoteId('folder');

    $this->assertTrue($object instanceof idObject);
    $this->assertTrue($object->id == $folder->id);
    $this->assertEquals((string)$object->name, __FUNCTION__);
  }

  public function testRetrieveByClassIdentifier()
  {
    $objects = idObjectRepository::retrieveByClassIdentifier('folder');

    $this->assertEquals('eZ Publish', $objects[0]['name']);
    $this->assertEquals('7', count($objects));
  }

  public function testRetrieveByTextAttribute()
  {
    $fixture = dirname(__FILE__) . '/../fixtures/classes.yml';
    $data = new ezpYamlData();
    $data->loadClassesData($fixture);
    
    $folder = new idObject("folder", 2);
    $folder->name = 'example';
    $folder->short_description = "123example";
    $folder->store();
    $folder->publish();

    $object = idObjectRepository::retrieveByTextAttribute('folder', 'folder/name', "example");
    $this->assertEquals('example', (string)$object->name);

    $FrontPage = new idObject("FrontPage", 2);
    $FrontPage->name = 'front page search attribute';
    $FrontPage->banner_link = 'front_page_url';
    $FrontPage->store();
    $FrontPage->publish();

    $new_object = idObjectRepository::retrieveByTextAttribute('FrontPage', 'FrontPage/banner_link', "front_page_url");
    $this->assertEquals('front page search attribute', (string)$new_object->name);
  }

  public function testRetrieveByTextAttributeCustomField()
  {
    $fixture = dirname(__FILE__) . '/../fixtures/classes.yml';
    $data = new ezpYamlData();
    $data->loadClassesData($fixture);

    $FrontPage = new idObject("FrontPage", 2);
    $FrontPage->name = 'front page search attribute';
    $FrontPage->banner_link = 'front_page_url';
    $FrontPage->store();
    $FrontPage->publish();

    $new_object = idObjectRepository::retrieveByTextAttribute('frontpage', 'frontpage/banner_link', "front_page_url");
    $this->assertEquals('front page search attribute', (string)$new_object->name);
  }
}

?>