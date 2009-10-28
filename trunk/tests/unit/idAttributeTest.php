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
}

?>