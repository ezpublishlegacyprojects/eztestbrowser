<?php

class FunctionalTest extends eZBrowserTestCase
{
  
  protected $fixtures_classes = 'extension/eztestbrowser/tests/functional/fixtures/classes.yml';
  
  protected $fixtures_objects = 'extension/eztestbrowser/tests/functional/fixtures/objects.yml';
  
  protected function fixturesSetup()
  {

  }
  
  public function testNewsList()
  {
    $ini = eZINI::instance();
    
    if (!strstr($ini->variable('SiteSettings', 'SiteURL'), 'localhost'))
    {
      $this->markTestSkipped('This test run only in localhost way');
    }
    
    $this->get('http://' . $ini->variable('SiteSettings', 'SiteURL'));
    $this->checkElementResponse('h1', 'eZ Test Browser');
    $this->click('News');
    $this->checkElementResponse('h1', 'News', array('position' => 1));
    $this->checkElementResponse('div.content-view-children a', 10);
  }
}