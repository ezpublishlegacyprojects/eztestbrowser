<?php

class idClassRepository
{
  public static function addAttribute($id, $names = array(), $identifer = 'test_attribute', $type = 'ezstring', $attributes = array())
  {
    $classAttribute = eZContentClassAttribute::create( $id, $type);
    foreach ($names as $language => $name)
    {
      $classAttribute->setName( $name, $language );
    }
    $classAttribute->setAttribute( 'identifier', $identifer );
    $classAttribute->setAttribute( 'version', 0 );
    $classAttribute->setAttribute( 'placement', 3 );

    foreach($attributes as $name => $value)
    {
      if ($classAttribute->hasAttribute($name))
      {
        $classAttribute->setAttribute($name, $value);
      }
    }
    $classAttribute->store();

    return $classAttribute;
  }
}