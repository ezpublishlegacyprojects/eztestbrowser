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
  
  private function parseYaml($file)
  {
    return sfYaml::load($file);
  }

  private function getParentNodeId($parent_node_id)
  {
    if ((int)$parent_node_id == 0)
    {
      if (isset($this->object_ids[$parent_node_id]))
      {
        return $this->object_ids[$parent_node_id];
      }
    }
    return $parent_node_id;
  }
  
  private function createOrRetrieve($parameters, $class_identifier = null)
  {
    if (isset($parameters['id']))
    {
      return idObjectRepository::retrieveById($parameters['id']);
    }
    
    return new idObject($class_identifier);
  }
  
  private function loadAttributes($parameters, $object)
  {
    $data_map = $object->dataMap;
    
    foreach ($parameters['attributes'] as $name => $value)
    {
      if (isset($this->content_object_ids[$value]) && isset($data_map[$name]) && $data_map[$name]->attribute('data_type_string') == 'ezobjectrelation')
      {
        $value = $this->content_object_ids[$value];
      }
      $object->$name = $value;
    }
  }
  
  private function loadTranslations($parameters, $object)
  {
    foreach ($parameters['translations'] as $language_code => $attributes)
    {
      array_walk($attributes, 'remoteIdToId', array('map' => $this->content_object_ids, 'data_map' => $object->dataMap));
      eZContentLanguage::fetchByLocale($language_code, true);
      $object->addTranslation($language_code, $attributes);
    }
  }
  
  /**
   * Load multiple location for object
   *
   * @param ezpObject $object
   * @param array $object_parameters
   */
  private function loadLocations($parameters, $object)
  {
    if (!isset($parameters['locations']))
    {
      return;
    }
    
    if (!is_array($parameters['locations']))
    {
      throw new Exception('You must set locations data in yaml');
    }

    foreach ($parameters['locations'] as $index => $location)
    {
      $node = $object->addNode($this->getParentNodeId($location['parent_node_id']), $index == 'main');
      
      if (isset($location['priority']))
      {
        $node->setAttribute('priority', $location['priority']);
        $node->store();
      }
    }
  }
  
  private function setContentObjectMap($object)
  {
    $remote_id = $object->object->attribute('remote_id');
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
  
  public function loadObjectsData($file)
  {
    try 
    {
      $data = $this->parseYaml($file);
      
      foreach ($data as $object_class => $objects)
      {
        foreach ($objects as $remote_id => $object_parameters)
        {
          $object_parameters['remote_id'] = $remote_id;
          $object = $this->createOrRetrieve($object_parameters, $object_class);
          $object->hydrate($object_parameters);
          $object->object->store();
          
          $this->loadLocations($object_parameters, $object);
          $this->loadAttributes($object_parameters, $object);
          $this->loadTranslations($object_parameters, $object);
          
          $object->publish();
          
          $this->setContentObjectMap($object);
        }
      }
    }
    catch (Exception $e)
    {
      echo $e->getTraceAsString();
    }
  }

  
  
  public function loadClassesData($file)
  {
    try 
    {
      $data = $this->parseYaml($file);
  
      foreach ($data as $name => $class_data)
      {
        $class = new ezpClass($name, $this->getIdentifierFromName($name), $class_data['object_name']);
        $class->class->setAttribute('is_container', (bool)$class_data['is_container']);
        
        foreach ($class_data['attributes'] as $identifier => $attribute_data)
        {
          $attribute = $class->add($attribute_data['name'], $identifier, $attribute_data['type']);
          $this->setClassAttribute($attribute, 'can_translate', $attribute_data['can_translate']);
          $this->setClassAttribute($attribute, 'is_required', $attribute_data['is_required']);
          $attribute->store();
        }
  
        $attributes = $class->class->dataMap();
        
        foreach ($class_data['translations'] as $language_code => $language_data)
        {
          eZContentLanguage::fetchByLocale($language_code, true);
          $class->class->setName($language_data['name'], $language_code);
          
          foreach ($language_data['attributes'] as $identifier => $name)
          {
            $attributes[$identifier]->setName($name, $language_code);
            $attributes[$identifier]->store();
          }
        }
        
        $class->store();
        
        $this->classGroup = eZContentClassClassGroup::create( $class->id, $class->version, 1, 'Content');
        $this->classGroup->store();
      }
    }
    catch (Exception $e)
    {
      echo $e->getTraceAsString();
    }
  }

  
}

function remoteIdToId(&$value, $name, $parameters)
{
    if (isset($parameters['map'][$value]) && isset($parameters['data_map'][$name]) && $parameters['data_map'][$name]->attribute('data_type_string') == 'ezobjectrelation')
    {
      $value = $parameters['map'][$value];
    }
}
