<?php

class idObject extends ezpObject
{
  public function __construct($classIdentifier = false, $parentNodeID = false, $creatorID = 14, $section = 1)
  {
    if ($classIdentifier)
    {
      $this->class = eZContentClass::fetchByIdentifier($classIdentifier);
      $this->object = $this->class->instantiate($creatorID, $section);

      $this->setMainNode($parentNodeID);

      $this->nodes = array($this->mainNode);
    }
  }

  private function setMainNode($parent_node_id)
  {
    if (is_numeric($parent_node_id))
    {
      $this->mainNode = new ezpNode($this->object, $parent_node_id, true);
      $this->publish();
      $this->nodes[] = $this->mainNode;
    }
    
    return $this->mainNode->node;
  }
  
  public function addNode($parent_node_id, $is_main = false)
  {
    if ($is_main)
    {
      return $this->setMainNode($parent_node_id);
    }
    
    return parent::addNode($parent_node_id);
  }
  
  public function hydrate($object_parameters)
  {
    foreach ($object_parameters as $name => $value)
    {
      if($this->object->hasAttribute($name))
      {
        $this->object->setAttribute($name, $value);
      }
    }
    
  }
  
  public function fromeZContentObject($object)
  {
    $this->object = $object;
    $this->data_map = $this->object->dataMap();
    $this->class = $object->contentClass();
  }

  
  /**
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @param mixed $value
     * @ignore
     */
  public function __set($name, $value)
  {
    switch($name)
    {
      default:
        if (isset($this->dataMap[$name]))
        {
          $attribute = $this->dataMap[$name];
          switch($attribute->attribute('data_type_string'))
          {
            case 'ezdate':
            case 'ezobjectrelation':
              $attribute->fromString($value);
              break;
            default:
              parent::__set($name, $value);
              break;
          }

          $this->dataMap[$name]->store();
        }
        else
        {
          // eZPersistentObject sets a class properties to store
          // attribute information
          $this->$name = $value;
        }
    }
  }
  
     /**
     * Adds a translation in language $newLanguageCode for object
     *
     * @param string $newLanguageCode
     * @param mixed $translationData array( attribute identifier => attribute value )
     * @return void
     */
    public function addTranslation( $newLanguageCode, $translationData )
    {
        // Make sure to refresh the objects data.
        $this->refresh();

        $this->object->cleanupInternalDrafts();
        $version = $this->object->createNewVersionIn( $newLanguageCode );
        $version->setAttribute( 'status', eZContentObjectVersion::STATUS_INTERNAL_DRAFT );
        $version->store();

        $newVersion = $this->object->version( $version->attribute( 'version' ) );
        $newVersionAttributes = $newVersion->contentObjectAttributes( $newLanguageCode );

        $versionDataMap = self::createDataMap( $newVersionAttributes );

        // Start updating new version
        $version->setAttribute( 'modified', time() );
        $version->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );

        $db = eZDB::instance();
        $db->begin();

        $version->store();

        // @TODO: Add generic datatype support here

        foreach ( $translationData as $attr => $value )
        {
            if ( $versionDataMap[$attr]->attribute( 'data_type_string') == "ezxmltext" )
            {
                $value = $this->processXmlTextData( $value, $versionDataMap[$attr] );
            }

            $versionDataMap[$attr]->fromString($value);
            $versionDataMap[$attr]->store();
        }

        $db->commit();

        //Update the content object name
        $db->begin();
        $this->object->setName( $this->class->contentObjectName( $this->object,
                                                                 $version->attribute( 'version' ),
                                                                 $newLanguageCode ),
                                $version->attribute( 'version' ), $newLanguageCode );
        $db->commit();


        // Finally publish object
        self::publishContentObject( $this->object, $version );
    }

    private static function createDataMap($attributeArray)
    {
      $ret = array();
      foreach($attributeArray as $attribute)
      {
        $ret[$attribute->contentClassAttributeIdentifier()] = $attribute;
      }
      return $ret;
    }
    
    private function processXmlTextData($xml, $attribute)
    {
      $parser = new eZSimplifiedXMLInputParser($this->object->attribute( 'id' ));
      $parser->ParseLineBreaks = true;

      $xml = $parser->process($xml);
      $xml = eZXMLTextType::domString($xml);

      $urlIdArray = $parser->getUrlIDArray();
      if (count($urlIdArray) > 0)
      {
        eZSimplifiedXMLInput::updateUrlObjectLinks($attribute, $urlIdArray);
      }
      return $xml;
    }
}