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

class idObjectRepository
{
  protected $parameters;
  protected $array_object;
  
  public function __construct($parameters)
  {
    $this->parameters = $parameters;
  }

  public function setObjects(array $objects)
  {
    $this->array_object = new ArrayObject($objects);
  }

  public function toArray(array $keys)
  {
    $array = array();
    for($iterator = $this->array_object->getIterator(); $iterator->valid(); $iterator->next())
    {
      $array[] = idObjectRepository::retrieveFromeZContentObject($iterator->current()->object())->
                 toArray($keys);
    }
    return $array;
  }

  public function getArrayObject()
  {
    return $this->array_object;
  }

  /**
   * Proxy method to eZContentObject::fetch
   *
   * @param integer $id
   * @return idObject
   */
  public static function retrieveById($id)
  {
    $object = new idObject();
    $object->fromeZContentObject(eZContentObject::fetch($id));
    return $object;
  }

  /**
   * Proxy method to eZContentObject::fetchByNodeID
   *
   * @param integer $node_id
   * @return idObject
   */
  public static function retrieveByNodeId($node_id)
  {
    $object = new idObject();
    $object->fromeZContentObject(eZContentObject::fetchByNodeID($node_id));
    return $object;
  }

  /**
   * Proxy method to eZContentObject::fetchByRemoteId
   *
   * @param string $remote_id
   * @return idObject
   */
  public static function retrieveByRemoteId($remote_id)
  {
    $object = new idObject();
    $object->fromeZContentObject(eZContentObject::fetchByRemoteID($remote_id));
    return $object;
  }

  /**
   * Retrieve object list of same class for a date range
   *
   * @param string $class_identifier
   * @param string $date_start
   * @param string $date_stop
   * @return array
   */
  public static function retrieveByClassIdentifier($class_identifier, $conditions = array(), $asObject = false)
  {
    $class = eZContentClass::fetchByIdentifier($class_identifier, false);
    $conditions = array_merge($conditions, array('contentclass_id' => $class['id']));
   
    return eZContentObject::fetchFilteredList($conditions, false, false, $asObject);
  }

  /**
   * Based on parameterd value, create or retrieve an idObject
   * 
   * @return idObject
   */
  public function createOrRetrieve()
  {
    if ($this->parameters->has('id'))
    {
      return self::retrieveById($this->parameters->get('id'));
    }

    if ($this->parameters->has('class_identifier'))
    {
      return new idObject($this->parameters->get('class_identifier'));
    }

    throw new ezpInvalidObjectException('Impossible to create or retrieve an object. You need to pass an id or a class_identifier.');
  }

  /**
   * Retrieve the first object filtered by an attribute and its value
   *
   * @param string $attribute_identifier
   * @param mixed $value
   * @return mixed 
   */
  public static function retrieveByTextAttribute($class_identifier, $attribute_identifier, $value, $parentnode_id = 2)
  {
    $class_id = eZContentClass::classIDByIdentifier($class_identifier);
    $results_array = eZContentFunctionCollection::fetchObjectTree( $parentnode_id, false, false, false, 0, 1, 4, false, $class_id,
                                                                   array(array($attribute_identifier,'=',$value)), false, 'include',
                                                                   array($class_id), false, true, false, array(), true, false, true);

    if (isset($results_array['result']) && count($results_array['result']) > 0)
    {
      $object = new idObject();
      $object->fromeZContentObject($results_array['result'][0]->attribute('object'));
      return $object;
    }

    return null;
  }

  public static function retrieveFromeZContentObject(eZContentObject $content_object)
  {
    $object = new idObject();
    $object->fromeZContentObject($content_object);

    return $object;
  }
}
