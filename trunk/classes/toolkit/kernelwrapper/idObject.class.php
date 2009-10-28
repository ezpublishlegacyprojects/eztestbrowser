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

class idObject extends ezpObject
{
  protected $repository = null;
  protected $parser_errors = array();
  public $object;
  
  public function __construct($classIdentifier = false, $parentNodeID = false, $creatorID = 14, $section = 1)
  {
    if ($classIdentifier)
    {
      $this->class = eZContentClass::fetchByIdentifier($classIdentifier);

      if (!$this->class)
      {
        throw new ezpInvalidClassException($classIdentifier.' does not exists.');
      }

      $this->object = $this->class->instantiate($creatorID, $section);

      $this->setMainNode($parentNodeID);

      $this->nodes = (isset($this->mainNode)) ? array($this->mainNode) : array();
    }
  }

  public function setImportImageRepository($repository)
  {
    $this->repository = $repository;
  }

  public function getImportImageRepository()
  {
    return $this->repository;
  }

  public function setMainNode($parent_node_id)
  {
    if (is_numeric($parent_node_id))
    {
      $this->mainNode = new ezpNode($this->object, $parent_node_id, true);
      $this->publish();
      $this->nodes[] = $this->mainNode;
    }

    return (isset($this->mainNode)) ? $this->mainNode->node : null ;
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

    $this->store();
  }

  public function hydrateAttributes($attributes)
  {
    foreach ($attributes as $name => $value)
    {
      $this->$name = $value;
    }
  }

  public function fromeZContentObject(eZContentObject $object)
  {
    if (!$object)
    {
      throw new Exception('Object is invalid');
    }
    
    $this->object = $object;
    $this->data_map = $this->object->dataMap();
    $this->class = $object->contentClass();

    return $this;
  }

  public function fromArray($data)
  {
    $this->hydrate($data);
    $this->hydrateAttributes($data);
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
            case 'ezxmltext':
              if (mb_detect_encoding($value) != 'UTF-8')
              {
                $value = utf8_encode($value);
              }
              $attribute->fromString(idAttribute::processXmlTextData($value, $attribute, $this, $this->repository));
              break;
            case 'ezdate':
              if (preg_match('/\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?/', $value))
              {
                $value = strtotime($value);
              }
            case 'ezobjectrelation':
            case 'ezobjectrelationlist':
            case 'ezinteger':
            case 'ezboolean':
            case 'ezbinaryfile':
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
        eZContentLanguage::fetchByLocale($newLanguageCode, true);
        
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
                $value = $this->processXmlTextData( $value, $versionDataMap[$attr], $this->repository );
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

    private function processXmlTextData($value, $attribute, $repository = null)
    {
      return idAttribute::processXmlTextData($value, $attribute, $this, $repository);
    }

    /**
     * Returns the value of the property $name.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @ignore
     */
    public function __get($name) 
    {

      switch ($name) {
        case 'dataMap':
          if ( isset($this->object) )
          {
            return $this->object->dataMap();
          }
          return array();

        default:
          
          if (isset( $this->dataMap[$name]))
          {
            return new idAttribute($this->dataMap[$name], $this);
          }

          return $this->object->attribute( $name );
      }
    }

    public function setMainLocation($node_id)
    {
      $this->addNode($node_id, true);
    }

    public function addLanguage($language_code)
    {
      $this->addTranslation($language_code);
      $this->publish();
    }

    public function setParserError($parser_errors, $name)
    {
      $this->parser_errors[$name] = $parser_errors;
    }

    public function getParserError()
    {
      return $this->parser_errors;
    }

    public function getObject()
    {
      return $this->object;
    }
}