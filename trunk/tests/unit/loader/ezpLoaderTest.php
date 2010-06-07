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
    $fixture = dirname(__FILE__) . '/../fixtures/ezselection.yml';

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

  public function test_buildObject_with_different_default_language()
  {
    $loader = new eZMockYamlData();
    $loader->loadClassesData(dirname(__FILE__).'/../fixtures/classes/raccolta_regali.yml');
    $loader->loadObjectsData(dirname(__FILE__).'/../fixtures/shops.yml');

    $parameters = array(
      'locations' => array('main' => array('parent_node_id' => 'regali')),
      'class_identifier' => 'RaccoltaRegali',
      'remote_id' => 'quarantanove_e_novantanove',
      'attributes' => array(
                        'nome' => 'A partire da 49.99',
                        'a_partire_da' => 49.99,
                        'numero_regali' => 3,
                      )
      );

    try
    {
      $loader->buildObject($parameters);
    }
    catch (Exception $e)
    {
      $this->fail("generated excetion : ".$e->getMessage());
    }

  }
}

class eZMockYamlData extends ezpYamlData
{
  public function getMockParentNodeId($parent_node_id)
  {
    return $this->getParentNodeId($parent_node_id);
  }
}