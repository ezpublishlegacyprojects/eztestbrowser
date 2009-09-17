<?php

class idAttribute
{
  protected $attribute;

  public function __construct(eZContentAttribute $attribute)
  {
    $this->attribute = $attribute;
  }

  public function __toString()
  {
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

    throw new Exception($name . 'is invalid');
  }
}

?>
