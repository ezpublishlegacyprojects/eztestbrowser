<?php

class idAttribute
{
  protected $object;
  protected $attribute;

  public function __construct(eZContentObjectAttribute $attribute, idObject $object)
  {
    $this->object = $object;
    $this->attribute = $attribute;
  }

  public function __toString()
  {
    if (method_exists($this->attribute, 'toString'))
    {
      return $this->attribute->toString();
    }

    return $this->attribute->content();
  }

  public function __get($name)
  {
    if ($this->attribute->hasAttribute($name))
    {
      return $this->attribute->attribute($name);
    }

    $language_code = str_replace('_', '-', $name);
    $language = eZContentLanguage::fetchByLocale($language_code);

    if ($language)
    { 
      return new idAttribute($this->attribute->language($language_code));
    }

    if (isset($this->attribute->$name))
    {
      return $this->attribute->$name;
    }
    
    throw new Exception($name . 'is invalid');
  }

  public function __set($name, $value)
  {
    if ($this->attribute->hasAttribute($name))
    {
      $this->attribute->setAttribute($name, $value);
      return $this->attribute->store();
    }

    $language_code = str_replace('_', '-', $name);
    $language = eZContentLanguage::fetchByLocale($language_code);

    if ($language)
    {
      $attribute = $this->attribute->language($language_code);

      if (!$attribute)
      {
        $this->object->addLanguage($language_code);
        $attribute = $this->attribute->language($language_code);
      }
      
      $attribute->fromString($value);
      return $attribute->store();
    }

    if (isset($this->attribute->$name))
    {
      $this->attribute->$name = $value;
      return $this->attribute->store();
    }

    throw new Exception($name . 'is invalid');
  }

  public function __call( $name, $arguments )
  {
    return call_user_func_array(array($this->attribute, $name), $arguments);
  }
}

?>
