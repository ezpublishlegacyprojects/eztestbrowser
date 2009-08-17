<?php

class FunctionalTest extends eZBrowserTestCase
{
  
  protected $fixtures_classes = 'extension/eztestbrowser/tests/functional/fixtures/classes.yml';
  
  protected $fixtures_objects = 'extension/eztestbrowser/tests/functional/fixtures/objects.yml';
  
  public function testNewsList()
  {
    $ini = eZINI::instance();
    
    $this->get('http://' . $ini->variable('SiteSettings', 'SiteURL'));
    $this->checkElementResponse('h1', 'eZ Test Browser');
    $this->click('News');
    $this->checkElementResponse('h1', 'News', array('position' => 1));
    $this->checkElementResponse('div.content-view-children a', 10);
  }
}