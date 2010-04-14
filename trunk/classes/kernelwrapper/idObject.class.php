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
  protected $errors = array();
  protected $database;
  
  public $object;
  
  public function __construct($classIdentifier = false, $parentNodeID = false, $creatorID = 14, $section = 1)
  {
    $this->database = eZDB::instance();

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

  public function hydrate($attributes, $only_data_map = false)
  {
    if (!is_array($attributes)) return;
    
    foreach ($attributes as $name => $value)
    {
      if($this->object->hasAttribute($name) && !$only_data_map)
      {
        $this->object->setAttribute($name, $value);
      }
      $this->$name = $value;
    }
    $this->store();
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

  public function fromeZContentObjectVersion(eZContentObjectVersion $object)
  {
    if (!$object)
    {
      throw new Exception('Object is invalid');
    }

    $this->object = $object;
    $this->data_map = $this->object->dataMap();

    return $this;
  }

  public function fromArray($data)
  {
    $this->hydrate($data);
  }

  /*
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @param mixed $value
     * @ignore
     */
  public function __set($name, $value)
  { 
    try
    {
      $attribute = new idAttribute($this, $name);
      $attribute->fromString($value);
    }
    catch(ezpInvalidObjectAttributeException $e)
    { 
      $this->object->setAttribute($name, $value);
    }
  }

  protected function addVersionIn($new_language_code)
  {
    eZContentLanguage::fetchByLocale($new_language_code, true);
    $this->refresh();
    $this->object->cleanupInternalDrafts();

    $version = $this->object->createNewVersionIn($new_language_code);
    $version->setAttribute('modified', time());
    $version->setAttribute('status', eZContentObjectVersion::STATUS_DRAFT);
    $version->store();
    
    return $version;
  }

  /*
   * Adds or update a translation in the language $newLanguageCode
   *
   * @param string $newLanguageCode
   * @param mixed $translationData array( attribute identifier => attribute value )
   * @return void
   */
  public function addOrUpdateTranslation( $newLanguageCode, $translationData )
  {
    
    if(in_array($newLanguageCode, array_keys($this->object->allLanguages())))
    {
      return $this->updateTranslation($newLanguageCode, $translationData);
    }

    return $this->addTranslation($newLanguageCode, $translationData);
  }

  /**
   * Update an existing translation object translation
   *
   * @param string $newLanguageCode the language code such as "eng-GB" or "ita-IT"
   * @param mixed  $translationData array( attribute identifier => attribute value )
   */
  public function updateTranslation($newLanguageCode, $translationData)
  {
    $this->database->begin();
    $newLanguageCode = str_replace('-', '_', $newLanguageCode);
    // @TODO: Add generic datatype support here
    foreach ($translationData as $attr => $value)
    {
        $this->$attr->$newLanguageCode = $value;
    }

    $this->database->commit();
    
    $this->publish();
  }

  /*
   * Adds a translation in language $newLanguageCode for object
   *
   * @param string $newLanguageCode
   * @param mixed $translationData array( attribute identifier => attribute value )
   * @return void
   */
  public function addTranslation( $newLanguageCode, $translationData )
  {
    $version = $this->addVersionIn($newLanguageCode);

    $new_version = new idObject();
    $new_version->fromeZContentObjectVersion($version);

    $versionDataMap = $version->dataMap();

    $this->database->begin();

    // @TODO: Add generic datatype support here
    foreach ($translationData as $attr => $value)
    {
        if ($versionDataMap[$attr]->attribute('data_type_string') == "ezxmltext")
        {
            $value = idAttribute::processXmlTextData($value, $versionDataMap[$attr], $this, $this->repository);
        }
       
        $versionDataMap[$attr]->fromString($value);
        $versionDataMap[$attr]->store();
    }

    $this->database->commit();
    
    $this->updateName($version, $newLanguageCode);
    $this->publishContentObject($this->object, $version);
  }

  protected function updateName($version, $newLanguageCode)
  {
    //Update the content object name
    $this->database->begin();
    $this->object->setName( $this->class->contentObjectName( $this->object,
                                                             $version->attribute( 'version' ),
                                                             $newLanguageCode ),
                            $version->attribute( 'version' ), $newLanguageCode );
    $this->database->commit();
  }

  /*
   * Returns the value of the property $name.
   *
   * @throws ezcBasePropertyNotFoundException if the property does not exist.
   * @param string $name
   * @ignore
   */
  public function __get($name)
  {
    switch ($name)
    {
      case 'dataMap':
        if ( isset($this->object) )
        {
          return $this->object->dataMap();
        }
        return array();

      default:
        try
        {
          return new idAttribute($this, $name);
        }
        catch(Exception $e)
        {
          return $this->object->attribute( $name );
        }
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

  public function setParserError($errors, $name)
  {
    if (count($errors) > 0)
    {
      $this->errors[$name] = $errors;
    }
  }

  public function countErrors()
  {
    return count($this->errors);
  }
  
  public function getParserError()
  {
    return $this->errors;
  }

  public function getObject()
  {
    return $this->object;
  }

  public function getClass()
  {
    return $this->class;
  }
}
