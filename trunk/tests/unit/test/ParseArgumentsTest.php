<?php

class ParseArgumentsTest extends PHPUnit_Framework_TestCase
{
  public function testGetSiteacces()
  {
    $this->assertTrue(false === ParseArguments::getSiteaccess(array()));
    $this->assertTrue(false === ParseArguments::getSiteaccess(array('--debug', '-f', '--dsn=mysql://exa:bla@dev/dev')));
    $this->assertTrue(false === ParseArguments::getSiteaccess(array('--debug', '-f', '--dsn=mysql://exa:bla@dev/dev', 'extension/ezdestefani/')));

    $this->assertEquals('access_site', ParseArguments::getSiteaccess(array('--debug', '-f', '--dsn=mysql://exa:bla@dev/dev', '--site-access=access_site')));
  }
}