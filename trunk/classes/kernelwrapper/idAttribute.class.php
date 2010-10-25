<?php

class idAttribute
{
  protected $object;
  protected $attribute;
  protected $name;

  public function __construct(idObject $object, $name, $language_code = null)
  {
    if(!($object instanceof idObject))
    {
      throw new ezpInvalidObjectException('Object is mandatory');
    }
    
    if(!isset($object->dataMap[$name]) || !($object->dataMap[$name] instanceof eZContentObjectAttribute))
    {
      throw new ezpInvalidObjectAttributeException('Attribute "'.$name.'" is invalid');
    }

    $this->object = $object;
    $this->name = $name;

    if (is_null($language_code))
    {
      $this->attribute = $object->dataMap[$name];
    }
    else
    {
      $this->attribute = $object->dataMap[$name]->language($language_code);
    }
    
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
      return new idAttribute($this->object, $this->name, $language_code);
    }

    if (isset($this->attribute->$name))
    {
      return $this->attribute->$name;
    }
    
    throw new Exception($name . ' is invalid');
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
      /**
       * @TODO: questo codice è duplicato
       */
      switch( $attribute->attribute( 'data_type_string' ) )
      {
        case 'ezprice':
        case 'ezkeyword':
        case 'ezuser':
          $attribute->fromString($value);
          $attribute->store();
          break;
        case 'ezurl':
        case 'ezimage':
          $value = $this->cleanImagePath($value);
        case 'ezbinaryfile':
          $attribute->fromString($value);
          break;
        case 'ezdate':
          if (preg_match('/\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?/', $value))
          {
            $value = strtotime($value);
          }
          $attribute->fromString($value);
          break;
        case 'ezxmltext':
          $value = $this->processXmlTextData( $value, $attribute, $this->object, $this->object->getImportImageRepository());
        case 'ezpage':
        default:
          $attribute->setAttribute('data_text', $value);
      }

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

  public static function processXmlTextData($xml, $attribute, $object, $repository = null)
  {
      $parser = new idXmlInputParser($attribute->attribute('id'));

      if ($repository)
      {
        $parser->setRepository($repository);
      }
      
      $parser->ParseLineBreaks = true;

      $xml = $parser->process($xml);

      $class_attribute = eZContentClassAttribute::fetch($attribute->attribute('contentclassattribute_id'));
      $object->setParserError($parser->getErrors(), $class_attribute->attribute('identifier'));

      if (!$xml)
      {
        throw new Exception('Invalid xml');
      }
      
      $xml = eZXMLTextType::domString($xml);

      $urlIdArray = $parser->getUrlIDArray();
      if (count($urlIdArray) > 0)
      {
        eZOEXMLInput::updateUrlObjectLinks($attribute, $urlIdArray);
      }
      return $xml;
  }

  public static function cleanImagePath($value)
  {
    return preg_replace('|<script[^>]*>[^<]*</script>|im', '', $value);
  }

  public function fromString($value)
  {
    switch($this->attribute->attribute('data_type_string'))
    {
      case 'ezsimpleselection':
          $this->attribute->setAttribute('data_text', $value);
          $this->attribute->store();
        break;
      case 'ezxmltext':
        $this->attribute->fromString(idAttribute::processXmlTextData($value, $this->attribute, $this->object, $this->object->repository));
        break;
      case 'ezpage':
          $this->attribute->setAttribute('data_text', $value);
          $this->attribute->store();
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
      case 'ezprice':
      case 'ezkeyword':
      default:
        $this->attribute->fromString($value);
        $this->attribute->store();
        break;
    }

    $this->attribute->store();
  }

  public function getOriginalAttribute()
  {
    return $this->attribute;
  }
}

