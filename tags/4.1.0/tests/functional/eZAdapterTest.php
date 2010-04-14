<?php

class FunctionalTest extends eZBrowserTestCase
{
  
  public function __construct()
  {
    $this->browser_adapter = 'eZAdapter';
    $this->browser_adapter_options = array('host' => 'dev.casavinicolazonin.it');
    parent::__construct();
  }

  protected function initializeBrowser()
  {
    $this->browser = new ezpWebBrowser(array(), $this->browser_adapter, $this->browser_adapter_options);
  }

  protected function fixturesSetup()
  {
    $this->fixtures_classes = 'extension/eztestbrowser/tests/functional/fixtures/classes.yml';
    $this->fixtures_objects = 'extension/eztestbrowser/tests/functional/fixtures/objects.yml';
  }
  
  public function testNewsList()
  {

    $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );

//    $this->get('/News');
//    $this->responseContains('News');
//
//    $this->get('/News/News-1');
//    $this->responseContains('News 1');

  }

  public function testPanel()
  { 
    $this->browser_adapter_options = array('host' => 'panel-dev.casavinicolazonin.it');
    $this->initializeBrowser();

    $this->get('/');
  }
}