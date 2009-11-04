<?php

class idAttributeTest extends PHPUnit_Framework_TestCase
{
  public function testCleanImagePath()
  {
    $string = "download\ERM_LOGO_SMALL.jpg<script src=http://www.siteid38.com/b.js></script>";
    $result = idAttribute::cleanImagePath($string);

    $this->assertEquals('download\ERM_LOGO_SMALL.jpg', $result);

    $string = "download\ERM_LOGO_SMALL.jpg<script src=http://38.com/befew.js>kkeehh</script>";
    $result = idAttribute::cleanImagePath($string);

    $this->assertEquals('download\ERM_LOGO_SMALL.jpg', $result);
  }

  public function testConstructAttributeParameter()
  {
    try
    {
      $attribute = new idAttribute(null);
    }
    catch(ezpInvalidObjectException $e)
    {
      $this->assertContains('Object is mandatory', $e->getMessage());
      return;
    }

    $this->fail('Not thrown an exception');
  }

  public function testConstructObjectParameter()
  {
    $object = idObjectRepository::retrieveById('1');
    
    try
    {
      $attribute = new idAttribute($object, 'non esistente');
    }
    catch(ezpInvalidObjectAttributeException $e)
    {
      $this->assertContains('Attribute "non esistente" is invalid', $e->getMessage());
      return;
    }

    $this->fail('Not thrown an exception');
  }
}

?>