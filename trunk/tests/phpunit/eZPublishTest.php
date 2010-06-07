<?php

include_once(dirname(__FILE__).'/../../classes/PHPUnit/Extensions/WebBrowserTestCase.php');
include_once(dirname(__FILE__).'/../../classes/test/eZAdapter.class.php');

/**
 * Description of eZPublishTest
 *
 * @author cphp
 */
class eZPublishTest extends PHPUnit_Extensions_WebBrowserTestCase
{

  public function setUp()
  {
    $this->browser_adapter = 'eZAdapter';
    $this->initializeBrowser();
  }

  public function testIndex()
  {
    $this->get('/');
  }
}
