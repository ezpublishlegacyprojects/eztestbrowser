<?php

class idObjectRepository
{
  public static function retrieveById($id)
  {
    $object = new idObject();
    $object->fromeZContentObject(eZContentObject::fetch($id));
    return $object;
  }

  public static function retrieveByNodeId($node_id)
  {
    $object = new idObject();
    $object->fromeZContentObject(eZContentObject::fetchByNodeID($node_id));
    return $object;
  }
}