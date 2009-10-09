<?php

class ezpControllerTest extends ezpDatabaseTestCase
{
  protected $backupGlobals = true;

  protected function emptyEnvironment()
  {
    unset($_SERVER['REQUEST_URI'],
          $_SERVER['HTTP_HOST'],
          $_SERVER['SERVER_NAME'],
          $_SERVER['HTTP_USER_AGENT'],
          $_SERVER['DOCUMENT_ROOT'],
          $_SERVER['SCRIPT_FILENAME'],
          $_SERVER['SCRIPT_NAME'],
          $_SERVER['SERVER_PORT'],
          $_SERVER['SERVER_PROTOCOL'],
          $_SERVER['REQUEST_METHOD']
        );

  }

  public function setup()
  {
    $this->emptyEnvironment();
  }

  public function tearDown()
  {
    $this->emptyEnvironment();
  }

  public function testBackendController()
  {
    $this->markTestSkipped();
    $_SERVER['REQUEST_URI']     = '/user/login';
    $_SERVER['HTTP_HOST']       = 'panel-dev.casavinicolazonin.it';

    $_SERVER['SERVER_NAME']     = $_SERVER['HTTP_HOST'];
    $_SERVER['HTTP_USER_AGENT'] = 'ezpAdapter';
    $_SERVER['DOCUMENT_ROOT']   = dirname(__FILE__.'/../../../../../');
    $_SERVER['SCRIPT_FILENAME'] = dirname(__FILE__.'/../../../../../').'index.php';
    $_SERVER['SCRIPT_NAME']     = '/index.php';
    $_SERVER['SERVER_PORT']     = '80';
    $_SERVER["SERVER_PROTOCOL"] = "HTTP/1.1";
    $_SERVER["REQUEST_METHOD"]  = 'GET';


    $controller = new ezpController();
    $output = $controller->dispatch();
    unset($controller);

    file_put_contents('/tmp/output.html', $output);
    $this->assertContains('amministrazione di eZ Publish', $output);
    
  }

  public function testFrontendController()
  {
    $this->markTestSkipped();
    $_SERVER['REQUEST_URI']     = '/user/login';
    $_SERVER['HTTP_HOST']       = 'dev.casavinicolazonin.it';

    $_SERVER['SERVER_NAME']     = $_SERVER['HTTP_HOST'];
    $_SERVER['HTTP_USER_AGENT'] = 'ezpAdapter';
    $_SERVER['DOCUMENT_ROOT']   = dirname(__FILE__.'/../../../../../');
    $_SERVER['SCRIPT_FILENAME'] = dirname(__FILE__.'/../../../../../').'index.php';
    $_SERVER['SCRIPT_NAME']     = '/index.php';
    $_SERVER['SERVER_PORT']     = '80';
    $_SERVER["SERVER_PROTOCOL"] = "HTTP/1.1";
    $_SERVER["REQUEST_METHOD"]  = 'GET';
    
    $controller = new ezpController();
    $output = $controller->dispatch();
    unset($controller);
    

    $this->assertContains('Casa Vinicola Zonin', $output);
  }

}