<?php

class idClassRepositoryTest extends idDatabaseTestCase
{
  public function testAddAttribute()
  {
    $class = eZContentClass::fetchByIdentifier('article', true, 0);
    $attributes = array('is_information_collector' => true, 'is_required' => true);

    idClassRepository::addAttribute($class->attribute('id'), array('eng-GB' => 'Name', 'ita-IT' => 'Nome'), 'name', 'ezstring', $attributes);

    $attributes = $class->dataMap();

    $this->assertTrue((bool)$attributes['name']->attribute('is_information_collector'));
    $this->assertTrue((bool)$attributes['name']->attribute('is_required'));
  }

  public function testAddAttributeToExistingObject()
  {
    $object = new idObject('article', 2);
    $object->remote_id = 'my_article';
    $object->title = 'titolo';
    $object->store();
    $object->publish();
    eZContentObject::clearCache();

    $class = eZContentClass::fetchByIdentifier('article', true, 0);
    idClassRepository::addAttribute($class->attribute('id'), array('eng-GB' => 'expert', 'ita-IT' => 'esperto'), 'esperto', 'ezstring');

    $my_object = idObjectRepository::retrieveByRemoteId('my_article');
    $this->assertTrue($my_object->esperto instanceof idAttribute);
  }
}