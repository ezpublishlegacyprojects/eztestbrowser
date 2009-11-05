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
}