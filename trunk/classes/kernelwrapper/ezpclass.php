<?php

/**
 * eZ Publish content class kernel wrapper
 *
 * <code>
 * $class = new ezpClass();
 * $name = $class->add( 'Name', 'name', 'ezstring' );
 * $relations = $class->add( 'Relations', 'relations', 'ezobjectrelationlist' );
 * $email = $class->add( 'E-mail', 'email', 'ezemail' );
 *
 * $class->remove( $email );
 * $class->store();
 * </code>
 */

 /**
  * TODO: add possibility for translating content class
  * TODO: replace ezpClass params with struct
  * TODO: add check for type string (only registered datatypes should be allowed) in ezpClass::add()
  */
class ezpClass
{
  /**
   * Initialize ezpClass object
   *
   * @param string $name
   * @param string $identifier
   * @param string $contentObjectName
   * @param int $creatorID
   * @param string $language
   * @param int $groupID
   * @param string $groupName
   */
  public function __construct( $name = 'Test class', $identifier = 'test_class', $contentObjectName = '<test_attribute>', $creatorID = 14, $language = 'eng-GB', $groupID = 1, $groupName = 'Content' )
  {
      if ( eZContentLanguage::fetchByLocale( $language ) === false )
      {
          $topPriorityLanguage = eZContentLanguage::topPriorityLanguage();

          if ( $topPriorityLanguage )
              $language = $topPriorityLanguage->attribute( 'locale' );
      }

      $this->language = $language;

      $this->class = eZContentClass::create( $creatorID, array(), $this->language );
      $this->class->setName( $name, $this->language );
      $this->class->setAttribute( 'contentobject_name', $contentObjectName );
      $this->class->setAttribute( 'identifier', $identifier );
      $this->class->store();

      $languageID = eZContentLanguage::idByLocale( $this->language );
      $this->class->setAlwaysAvailableLanguageID( $languageID );

      $this->classGroup = eZContentClassClassGroup::create( $this->id, $this->version, $groupID, $groupName );
      $this->classGroup->store();
  }

  /**
   * Adds new content class attribute to initialized class.
   *
   * @param string $name
   * @param string $identifier
   * @param string $type
   * @return eZContentClassAttribute
   */
  public function add( $name = 'Test attribute', $identifer = 'test_attribute', $type = 'ezstring' )
  {
      $classAttribute = eZContentClassAttribute::create( $this->id, $type, array(), $this->language );

      $classAttribute->setName( $name, $this->language );

      $dataType = $classAttribute->dataType();

      if (!$dataType)
      {
        throw new Exception('Impossible to create a '.$type.' attribute.');
      }

      $dataType->initializeClassAttribute( $classAttribute );

      $classAttribute->setAttribute( 'identifier', $identifer );
      $classAttribute->store();

      return $classAttribute;
  }

  /**
   * Remove given eZContentClassAttribute object from initialized class.
   *
   * @param eZContentClassAttribute $classAttribute
   * @return void
   */
  public function remove( eZContentClassAttribute $classAttribute )
  {
      $this->class->removeAttributes( array( $classAttribute ) );
  }

  /**
   * Stores defined version of content class.
   *
   * @return void
   */
  public function store()
  {
      $this->class->storeDefined( $this->class->fetchAttributes() );
  }

  /**
   * Returns the value of the property $name.
   *
   * @param string $name
   * @ignore
   */
  public function __get( $name )
  {
      return $this->class->attribute( $name );
  }

  /**
   * Sets the property $name to $value.
   *
   * @param string $name
   * @param mixed $value
   * @ignore
   */
  public function __set( $name, $value )
  {
      $this->$name = $value;
  }

  /**
   * Add class attributes from array
   *
   * @param array $data
   */
  public function fromArray($data)
  {
    foreach($data as $key => $value)
    {
      if($this->hasAttribute($key))
      {
        $this->setAttribute($key, $value);
      }
    }
  }

  /**
   * Add data map attributes class from an array
   *
   * @param array $attributes
   * @return array
   */
  public function addAttributesFromArray($attributes)
  {
    foreach ($attributes as $identifier => $attribute_data)
    {
      $attribute = $this->add($attribute_data['name'], $identifier, $attribute_data['type']);

      if (isset($attribute_data['can_translate']))
      {
        $attribute->setAttribute('can_translate', $attribute_data['can_translate']);
      }

      if (isset($attribute_data['is_required']))
      {
        $attribute->setAttribute('is_required', $attribute_data['is_required']);
      }

      if (isset($attribute_data['is_information_collector']))
      {
        $attribute->setAttribute('is_information_collector', $attribute_data['is_information_collector']);
      }

      if (isset($attribute_data['data_text2']))
      {
        $attribute->setAttribute('data_text2', $attribute_data['data_text2']);
      }

      if (isset($attribute_data['data_text4']))
      {
        $attribute->setAttribute('data_text4', $attribute_data['data_text4']);
      }

      if (isset($attribute_data['options']) && $attribute_data['type'] == 'ezselection')
      {
        // Serialize XML
        $doc = new DOMDocument('1.0', 'utf-8');
        $root = $doc->createElement("ezselection");
        $doc->appendChild($root);

        $options = $doc->createElement("options");

        $root->appendChild($options);
        foreach ($attribute_data['options'] as $index => $value)
        {
            $optionNode = $doc->createElement("option");
            $optionNode->setAttribute('id', $index);
            $optionNode->setAttribute('name', $value);

            $options->appendChild($optionNode);
        }

        $xml = $doc->saveXML();
        $attribute->setAttribute('data_text5', $xml);
      }

      $attribute->store();
    }

    return $this->class->dataMap();
  }

  /**
   * Proxy function to eZContentClass methods
   *
   * @param string $name
   * @param array $arguments
   * @return mixed
   */
  public function __call($name, $arguments)
  {
    if (method_exists($this->class, $name))
    {
      return call_user_func_array(array($this->class, $name), $arguments);
    }
    return false;
  }

  /**
   * Add attributes translated from an array
   *
   * @param array $attributes
   * @param string $language_code
   */
  public function addAttributesTranslationsFromArray($attributes, $language_code)
  {
    $data_map = $this->class->dataMap();    
    
    foreach ($attributes as $identifier => $name)
    {
      if (!isset($data_map[$identifier]))
      {
        throw new Exception('Attribute '.$identifier.' doesn\'t exists');
      }
      
      $this->output("\tsetting attribute name: ".$identifier);
      $data_map[$identifier]->setName($name, $language_code);
      $data_map[$identifier]->store();
    }
  }

  /**
   * Add a class to group
   *
   * @param string $group
   */
  public function addToGroup($group)
  {
    $classGroup = eZContentClassClassGroup::create($this->id, $this->version, 1, $group);
    $classGroup->store();
  }

  /**
   * Add translations data from array
   *
   * @param array $translations
   */
  public function addTranslationsFromArray($translations)
  {
    foreach ($translations as $language_code => $language_data)
    {
      eZContentLanguage::fetchByLocale($language_code, true);
      $this->setName($language_data['name'], $language_code);
      if (isset($language_data['attributes']))
      {
        $this->addAttributesTranslationsFromArray($language_data['attributes'], $language_code);
      }
    }
  }

    /**
   * Log messages
   *
   * @param string $message
   */
  protected function output($message)
  {
    if ($this->verbose)
    {
      echo $message."\n";
    }
  }
}

