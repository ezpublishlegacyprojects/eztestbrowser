<?php

class idClassRepository
{
  public static function addAttribute($id, $names = array(), $identifer = 'test_attribute', $type = 'ezstring')
  {
    $classAttribute = eZContentClassAttribute::create( $id, $type);
    foreach ($names as $language => $name)
    {
      $classAttribute->setName( $name, $language );
    }
    $classAttribute->setAttribute( 'identifier', $identifer );
    $classAttribute->setAttribute( 'version', 0 );
    $classAttribute->setAttribute( 'placement', 3 );
    $classAttribute->store();

    return $classAttribute;
  }
}