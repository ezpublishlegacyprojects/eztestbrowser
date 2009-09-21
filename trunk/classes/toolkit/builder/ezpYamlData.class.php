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

class ezpYamlData
{
  protected $object_ids = array();

  protected $content_object_ids = array();

  protected $object_parameters;

  private function parseYaml($file)
  {
    if (!file_exists($file))
    {
      throw new Exception('File '. $file .' does not exist');
    }
    
    return sfYaml::load($file);
  }

  private function setContentObjectMap($object)
  {
    $remote_id = $object->object->attribute('remote_id');
    if (!$object->object->mainNodeID())
    {
      throw new Exception('Object '.$remote_id.' doesn\'t have mainNodeID');
    }

    $this->object_ids[$remote_id] = $object->object->mainNodeID();
    $this->content_object_ids[$remote_id] = $object->id;
  }

  private function setClassAttribute($attribute, $attribute_name, $value)
  {
    if(isset($value))
    {
      $attribute->setAttribute($attribute_name, $value);
    }
  }

  private function getIdentifierFromName($name)
  {
    return strtolower($name);
  }

  protected function getParentNodeId($parent_node_id)
  {
    if ((int)$parent_node_id == 0)
    {
      if (isset($this->object_ids[$parent_node_id]))
      {
        return $this->object_ids[$parent_node_id];
      }

      $object = eZContentObject::fetchByRemoteID($parent_node_id);

      if ($object)
      {
        return $object->mainNode()->attribute('node_id');
      }
    }
    return $parent_node_id;
  }

  protected function createOrRetrieve($class_identifier = null)
  {
    if ($this->object_parameters->has('id'))
    {
      return idObjectRepository::retrieveById($this->object_parameters->get('id'));
    }

    return new idObject($class_identifier);
  }

  public function remoteIdToId($object, &$attributes)
  {
    array_walk($attributes, 'remoteIdToId', array('map' => $this->content_object_ids, 'data_map' => $object->dataMap));
  }

  private function loadTranslations($object)
  {
    if (!$this->object_parameters->has('translations'))
    {
      return;
    }

    if (!is_array($this->object_parameters->get('translations')))
    {
      throw new Exception('You must set translations data in yaml');
    }

    foreach ($this->object_parameters->get('translations') as $language_code => $attributes)
    {
      array_walk($attributes, 'remoteIdToId', array('map' => $this->content_object_ids, 'data_map' => $object->dataMap));
      $object->addTranslation($language_code, $attributes);
    }
  }

  /**
   * Load multiple location for object
   *
   * @param array $object_parameters
   * @param ezpObject $object
   */
  private function loadLocations($object)
  {
    if (!$this->object_parameters->has('locations'))
    {
      return;
    }

    if (!is_array($this->object_parameters->get('locations')))
    {
      throw new Exception('You must set locations data in yaml');
    }

    foreach ($this->object_parameters->get('locations') as $index => $location)
    {
      $this->output("\tAdding location $index....");
      $node = $object->addNode($this->getParentNodeId($location['parent_node_id']), $index == 'main');

      if (isset($location['priority']))
      {
        $node->setAttribute('priority', $location['priority']);
        $node->store();
      }
    }
  }

  /**
   * Load related objects
   *
   * @param array $parameters
   * @param idObject $object
   */
  private function loadRelated($object)
  {
    if (!$this->object_parameters->has('related'))
    {
      return;
    }

    if (!is_array($this->object_parameters->get('related')))
    {
      throw new Exception('You must set related data in yaml');
    }

    foreach ($this->object_parameters->get('related') as $object_remote_id)
    {
      if (isset($this->content_object_ids[$object_remote_id]))
      {
        $object_id = (int) $this->content_object_ids[$object_remote_id];
        if ($object->addContentObjectRelation($object_id) === false)
        {
          throw new Exception('Somthing wrong with related object '.$object_remote_id);
        }
      }
    }
  }
  
  /**
   * load url aliases for object
   *
   * @param idObject $object
   */
  private function loadUrlAlias($object)
  {
    if (!$this->object_parameters->has('url_alias'))
    {
      return;
    }
    
    if (!is_array($this->object_parameters->get('url_alias')))
    {
      throw new Exception('You must set url_alias data in yaml');
    }
    
    $language = eZContentLanguage::fetchByLocale( $object->currentLanguage(), false );
    foreach ($this->object_parameters->get('url_alias') as $url_alias)
    {
      $alias = eZURLAliasML::create($url_alias['value'], 'eznode:'.$object->mainNode()->attribute('node_id'), $url_alias['parent'], $language->attribute('id'));
      $alias->store();
    }
    
  }

  public function __construct($verbose = false)
  {
    $this->verbose = $verbose;
    $this->object_parameters = new sfParameterHolder();
  }

  private function output($message)
  {
    if ($this->verbose)
    {
      echo $message."\n";
    }
  }

  /**
   * Parse yaml file and build eZ Objects
   *
   * @param string $file
   */
  public function loadObjectsDataFromYaml($file)
  {
    $data = $this->parseYaml($file);
    $this->buildObjects($data);
  }

  /**
   * Parse yaml file and build ez objects
   *
   * @param string $file
   * @deprecated
   * @see loadObjectsDataFromYaml()
   */
  public function loadObjectsData($file)
  {
    $this->loadObjectsDataFromYaml($file);
  }

/**
 * Build eZ Publish object
 *
 * @param array $object_parameters
 * @return idObject
 */
  protected function buildObject($object_parameters)
  {
    $this->object_parameters->clear();
    $this->object_parameters->add($object_parameters);

    $this->output("creating ".$this->object_parameters->get('remote_id'));
    

    if (isset($this->content_object_ids[$this->object_parameters->get('id')]))
    {
      $this->object_parameters->set('id', $this->content_object_ids[$this->object_parameters->get('id')]);
    }

    $object = $this->createOrRetrieve($this->object_parameters->get('class_identifier'));
    $this->remoteIdToId($object, $this->object_parameters->get('attributes'));

    $object->hydrate($this->object_parameters->getAll());
    $object->hydrateAttributes($this->object_parameters->get('attributes'));

    $this->loadLocations($object);
    $this->loadTranslations($object);
    $object->publish();

    $this->loadRelated($object);
    $this->loadUrlAlias($object);
    $this->setContentObjectMap($object);

    return $object;
  }

  protected function buildObjects($data)
  {
    foreach ($data as $object_class => $objects)
    {
      $object_class = trim($object_class, '_');

      foreach ($objects as $remote_id => $object_parameters)
      {
        $object_parameters['class_identifier'] = $object_class;
        $object_parameters['remote_id'] = $remote_id;
        $this->buildObject($object_parameters);
      }
    }
  }

  public function loadClassesData($file)
  {

    $data = $this->parseYaml($file);

    foreach ($data as $name => $class_data)
    {
      $class = new ezpClass($name, $this->getIdentifierFromName($name), $class_data['object_name']);
      if (isset($class_data['is_container']))
      {
        $class->class->setAttribute('is_container', (bool)$class_data['is_container']);
      }

      foreach ($class_data['attributes'] as $identifier => $attribute_data)
      {
        $attribute = $class->add($attribute_data['name'], $identifier, $attribute_data['type']);

        if (isset($attribute_data['can_translate']))
        {
          $this->setClassAttribute($attribute, 'can_translate', $attribute_data['can_translate']);
        }

        if (isset($attribute_data['is_required']))
        {
          $this->setClassAttribute($attribute, 'is_required', $attribute_data['is_required']);
        }

        $attribute->store();
      }

      $attributes = $class->class->dataMap();
      $this->output('class name: '.$name);
      foreach ($class_data['translations'] as $language_code => $language_data)
      {
        eZContentLanguage::fetchByLocale($language_code, true);
        
        $class->class->setName($language_data['name'], $language_code);
        ezContentClassAttribute::removeObject($def);
        
        foreach ($language_data['attributes'] as $identifier => $name)
        {
          $this->output('setting attribute name: '.$identifier);
          $attributes[$identifier]->setName($name, $language_code);
          $attributes[$identifier]->store();
        }
      }

      $class->store();

      $this->classGroup = eZContentClassClassGroup::create( $class->id, $class->version, 1, 'Content');
      $this->classGroup->store();
    }
  }
}

function remoteIdToId(&$value, $name, $parameters)
{
  if (isset($parameters['data_map'][$name]))
  {
    switch($parameters['data_map'][$name]->attribute('data_type_string'))
    {
      case 'ezobjectrelation':
      case 'ezobjectrelationlist':
      case 'ezxmltext':
        $value = strtr($value, $parameters['map']);
        break;
    }
  }
}
