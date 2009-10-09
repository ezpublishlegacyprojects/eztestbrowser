<?php

/**
 * Description of ezpLoaderclass
 *
 * @author Francesco (cphp) Trucchia
 */
class ezpLoader
{
  protected $object_ids = array();
  protected $content_object_ids = array();
  protected $object_parameters;

  protected function setContentObjectMap($object)
  {
    $remote_id = $object->object->attribute('remote_id');
    if (!$object->object->mainNodeID())
    {
      throw new Exception('Object '.$remote_id.' doesn\'t have mainNodeID');
    }

    $this->object_ids[$remote_id] = $object->object->mainNodeID();
    $this->content_object_ids[$remote_id] = $object->id;
  }

  protected function setClassAttribute($attribute, $attribute_name, $value)
  {
    if(isset($value))
    {
      $attribute->setAttribute($attribute_name, $value);
    }
  }

  protected function getIdentifierFromName($name)
  {
    return strtolower($name);
  }

  protected function loadTranslations($object)
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

  /*
   * Load multiple location for object
   *
   * @param array $object_parameters
   * @param ezpObject $object
   */
  protected function loadLocations($object)
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

  /*
   * Load related objects
   *
   * @param array $parameters
   * @param idObject $object
   */
  protected function loadRelated($object)
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

  /*
   * load url aliases for object
   *
   * @param idObject $object
   */
  protected function loadUrlAlias($object)
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

  protected function output($message)
  {
    if ($this->verbose)
    {
      echo $message."\n";
    }
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

  /*
   * Build eZ Publish object
   *
   * @param array $object_parameters
   * @return idObject
   */
  public function buildObject($object_parameters)
  {
    
    $this->object_parameters->clear();
    $this->object_parameters->add($object_parameters);

    $this->output("creating ".$this->object_parameters->get('remote_id'));


    if (isset($this->content_object_ids[$this->object_parameters->get('id')]))
    {
      $this->object_parameters->set('id', $this->content_object_ids[$this->object_parameters->get('id')]);
    }

    $repository = new idObjectRepository($this->object_parameters);
    $object = $repository->createOrRetrieve();
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

  public function buildObjects($data)
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

  public function buildClasses($file)
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
        //ezContentClassAttribute::removeObject($def);

        foreach ($language_data['attributes'] as $identifier => $name)
        {
          $this->output("\tsetting attribute name: ".$identifier);
          $attributes[$identifier]->setName($name, $language_code);
          $attributes[$identifier]->store();
        }
      }

      $class->store();

      $this->classGroup = eZContentClassClassGroup::create( $class->id, $class->version, 1, 'Content');
      $this->classGroup->store();
    }
  }

  public function __construct($verbose = false)
  {
    $this->verbose = $verbose;
    $this->object_parameters = new sfParameterHolder();
  }

  public function remoteIdToId($object, &$attributes)
  {
    array_walk($attributes, 'remoteIdToId', array('map' => $this->content_object_ids, 'data_map' => $object->dataMap));
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

?>
