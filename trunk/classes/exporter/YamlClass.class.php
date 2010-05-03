<?php
/**
 * Export a eZ Publish class in yaml format for eZYamlLoader
 *
 * @author Francesco (cphp) Trucchia <ft@ideato.it>
 */
class ExporterYamlClass
{
  protected $class;
  protected $output;

  public function __construct(eZContentClass $class)
  {
    if(is_null($class))
    {
      throw new Exception('Class is mandatory, null given.');
    }
    
    $this->class = $class;
  }

  public function getOutput()
  {
    return sfYaml::dump($this->output, 3);
  }
  
  public function export()
  {
    $identifier = $this->class->attribute('identifier');

    $this->output[$identifier] = array();
    $this->output[$identifier]['object_name'] = $this->class->attribute('contentobject_name');
    $this->output[$identifier]['is_container'] = (bool)$this->class->attribute('is_container');

    $data_map = $this->class->dataMap();

    $attributes = array();
    foreach($data_map as $attribute)
    {
      $attribute_identifier = $attribute->attribute('identifier');
      $attributes[$attribute_identifier] = array();
      $attributes[$attribute_identifier]['name'] = $attribute->name();
      $attributes[$attribute_identifier]['type'] = $attribute->attribute('data_type_string');
      $attributes[$attribute_identifier]['is_required'] = (bool)$attribute->attribute('is_required');
      $attributes[$attribute_identifier]['is_information_collector'] = (bool)$attribute->attribute('is_information_collector');
      $attributes[$attribute_identifier]['can_translate'] = (bool)$attribute->attribute('can_translate');
    }
    
    $this->output[$identifier]['attributes'] = $attributes;

  }
}