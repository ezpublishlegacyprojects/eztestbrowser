<?php

class ezpControllerTest extends ezpDatabaseTestCase
{
  protected $backupGlobals = false;
  
  public function testController()
  {
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

    ob_start();
    require_once(dirname(__FILE__) . '/../../../../index_test.php');
    $output = ob_get_contents();
    ob_end_clean();
    ob_end_flush();

    

    $this->assertContains(' amministrazione di eZ Publish', $output);
  }

  public function testFrontendController()
  {
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

    ob_start();
    require_once(dirname(__FILE__) . '/../../../../index_test.php');
    $output = ob_get_contents();
    ob_end_clean();
    ob_end_flush();


    $this->assertContains(' Casa vinicola Zonin', $output);
  }
}