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

  public function __construct($parameters)
  {
    $this->parameters = $parameters;
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
   * Proxy method to eZContentObject::fetchByRemoteI
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
}