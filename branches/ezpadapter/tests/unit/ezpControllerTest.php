<?php

require_once(dirname(__FILE__).'/../../../../autoload.php');

class ezpControllerTest extends PHPUnit_Framework_TestCase
{

  protected function emptyEnvironment()
  {
    
    unset($_SESSION);
    unset($_COOKIE);
    unset($_SERVER);
    unset($_GET);
    unset($_REQUEST);
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
    $_SERVER['REQUEST_URI']     = '/plain_site_admin/user/login';
    $_SERVER['HTTP_HOST']       = 'localhost';

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

    $this->assertContains('amministrazione di eZ Publish', $output);
    
  }

  public function testFrontendController()
  {
    // $this->markTestSkipped();
    $_SERVER['REQUEST_URI']     = '/user/login';
    $_SERVER['HTTP_HOST']       = 'localhost';

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
    
    $this->assertContains('Login', $output);
    $this->assertContains('Plain site', $output);
  }

  public function testezpAdapterFrontend()
  {
    $browser = new ezpWebBrowser(array(), 'ezpAdapter');
    $browser->get('/plain_site/user/login');
    $this->assertContains('<h1><a href="/plain_site">Plain site</a></h1>', $browser->getResponseText());
    
    $browser->setField('Login', 'admin');
    $browser->setField('Password', 'publish');
    $browser->clickButton('Login');

    $this->assertContains('Refresh', $browser->getResponseText());
    $browser->followRedirect();

    $this->assertContains('Logout', $browser->getResponseText());
    $browser->click('Logout(Administrator User)');
    $browser->followRedirect();
    $this->assertContains('Login', $browser->getResponseText());
  }

  public function testezpAdapterBackend()
  {
    $backend = new ezpWebBrowser(array(), 'ezpAdapter');
    $backend->get('/plain_site_admin');
    $this->assertContains('amministrazione di eZ Publish', $backend->getResponseText());
    
    $backend->setField('Login', 'admin');
    $backend->setField('Password', 'publish');
    $backend->clickButton('Login');

    $this->assertContains('Refresh', $backend->getResponseText());
    $backend->followRedirect();

    $this->assertContains('Media', $backend->getResponseText());
  }

}

// function ezupdatedebugsettings() {}
