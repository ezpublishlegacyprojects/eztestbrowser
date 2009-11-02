<?php

class ezpLoaderTest extends idDatabaseTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->setName('ezpLoaderTest Tests');
  }

  public function testeZSelectionDatatyoeOptionsLoad()
  {
    $fixture = dirname(__FILE__) . '/fixtures/ezselection.yml';

    $data = new ezpYamlData();
    $data->loadClassesData($fixture);
    
    $attributes = eZContentClass::fetchByIdentifier('test')->dataMap();
    $options_xml = simplexml_load_string($attributes['options']->attribute('data_text5'));

    $this->assertEquals('1', (string)$options_xml->options->option[0]->attributes()->id);
    $this->assertEquals('opzione1', (string)$options_xml->options->option[0]->attributes()->name);

    $this->assertEquals('2', (string)$options_xml->options->option[1]->attributes()->id);
    $this->assertEquals('opzione2', (string)$options_xml->options->option[1]->attributes()->name);

    $this->assertEquals('3', (string)$options_xml->options->option[2]->attributes()->id);
    $this->assertEquals('opzione3', (string)$options_xml->options->option[2]->attributes()->name);
  }  
}