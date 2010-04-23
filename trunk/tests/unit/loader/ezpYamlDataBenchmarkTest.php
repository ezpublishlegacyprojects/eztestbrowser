<?php

class ezpYamlDataBenchmarkTest extends idDatabaseTestCase
{
  public function testeZSelectionDatatyoeOptionsLoad()
  {
    $classes = dirname(__FILE__) . '/../fixtures/benchmark/classes.yml';
    $objects = dirname(__FILE__) . '/../fixtures/benchmark/objects.yml';

    $data = new ezpYamlData();
    $data->loadClassesData($classes);
    $data->loadObjectsData($objects);
  }
}