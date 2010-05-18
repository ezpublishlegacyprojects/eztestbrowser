<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of eZBrowserTestCaseTest
 *
 * @author cphp
 */
class eZBrowserTestCaseTest extends eZBrowserTestCase
{  
  
  protected function fixturesSetUp()
  {
    $this->fixtures_classes = 'extension/eztestbrowser/tests/unit/fixtures/classes.yml';
    $this->fixtures_objects = 'extension/eztestbrowser/tests/unit/fixtures/objects.yml';
  }

  public function testLoadObjectsData()
  {
    $object = new idObject();
    $object->fromeZContentObject(eZContentObject::fetchByRemoteID('test'));

    $this->assertEquals('article', $object->class_identifier);
    $this->assertEquals('Test 2', (string)$object->title);
    $this->assertEquals(2, $object->section_id);
    $this->assertEquals(2, $object->getObject()->mainParentNodeId());

  }

  public function testLoadClassesData()
  { 
    $class = eZContentClass::fetchByIdentifier('News');

    if(!$class)
    {
      $this->fail('Class does not exitst');
    }
    
    $this->assertEquals($class->name(), 'News');
    $this->assertEquals($class->name('ita-IT'), 'Notizia');

    $this->assertEquals($class->attribute('contentobject_name'), '<title>');
    $this->assertTrue((bool)$class->attribute('is_container'));
    $this->assertTrue($class->inGroup('1'));
  }
}
