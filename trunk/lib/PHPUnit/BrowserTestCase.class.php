<?php
/**
* Copyright (C) 2009  Francesco trucchia
* 
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
* 
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.

* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*
* @author Francesco (cphp) Trucchia <ft@ideato.it>
* 
*/

require_once 'PHPUnit/Framework.php';

class BrowserTestCase extends PHPUnit_Framework_TestCase 
{
  /**
   * @var sfWebBrowser
   */
  protected $browser;
  protected $dom;
  protected $domCssSelector;
  protected $response;
  protected $verificationErrors = array();
  
  private function initialize()
  {
    $this->dom = $this->browser->getResponseDom();
    $this->response = $this->browser->getResponseText();
    $this->domCssSelector = $this->browser->getResponseDomCssSelector();
  }
  
  public function __construct()
  {
    parent::__construct();
    $this->browser = new sfWebBrowser(array(), null, array('cookies' => true, 'cookies_file' => 'cookies-zonin.txt', 'cookies_dir' => '/tmp'));
  }
  
  /**
   * Proxy get to browser
   *
   * @param string $url
   * @param array $parameters
   */
  public function get($url, $parameters = array())
  {
    $this->browser->get($url, $parameters);
    $this->initialize();
    
  }
  
  /**
   * Proxy back to browser
   *
   */
  public function back()
  {
    $this->browser->back();
    $this->initialize();
    
  }
  
  /**
   * Proxy method to sfWebBrowser::click()
   *
   * @param string $name
   * @param array $arguments
   */
  public function click($name, $arguments = array())
  {
    $this->browser->click($name, $arguments);
    $this->initialize();
  }
  
  /**
   * Proxy method to sfWebBrowser::setField()
   *
   * @param string $name
   * @param string $value
   */
  public function setField($name, $value)
  {
    $this->browser->setField($name, $value);
  }
  
  /**
   * Proxy method to sfWebBrowser::post()
   *
   * @param string $uri
   * @param array $parameters
   * @param array $headers
   */
  public function post($uri, $parameters = array(), $headers = array())
  {
    $this->browser->post($uri, $parameters, $headers);
    $this->initialize();
  }
  
  /**
   * Tests that the response matches a given CSS selector.
   *
   * @param  string $selector  The response selector or a sfDomCssSelector object
   * @param  mixed  $value     Flag for the selector
   * @param  array  $options   Options for the current test
   *
   */
  public function checkElementResponse($selector, $value = true, $options = array())
  {
    /*if (is_null($this->dom))
    {
      throw new Exception('The DOM is not accessible because the browser response content type is not HTML.');
    }*/

    if (is_object($selector))
    {
      $values = $selector->getValues();
    }
    else
    {
      $values = $this->domCssSelector->matchAll($selector)->getValues();
    }

    if (false === $value)
    {
      $this->assertEquals(0, count($values), sprintf('response selector "%s" does not exist', $selector));
    }
    else if (true === $value)
    {
      $this->greaterThan(0, count($values), sprintf('response selector "%s" exists', $selector));
    }
    else if (is_int($value))
    {
      $this->assertEquals($value, count($values), sprintf('response selector "%s" matches "%s" times', $selector, $value));
    }
    else if (preg_match('/^(!)?([^a-zA-Z0-9\\\\]).+?\\2[ims]?$/', $value, $match))
    {
      $position = isset($options['position']) ? $options['position'] : 0;
      if ($match[1] == '!')
      {
        $this->assertNotEquals(substr($value, 1), @$values[$position], sprintf('response selector "%s" does not match regex "%s"', $selector, substr($value, 1)));
      }
      else
      {
        $this->assertRegExp($value, @$values[$position], sprintf('response selector "%s" matches regex "%s" in "%s"', $selector, $value, @$values[$position]));
      }
    }
    else
    {
      $position = isset($options['position']) ? $options['position'] : 0;
      $this->assertEquals( $value, @$values[$position], sprintf('response selector "%s" matches "%s"', $selector, $value));
    }

    if (isset($options['count']))
    {
      $this->assertEquals($options['count'], count($values), sprintf('response selector "%s" matches "%s" times', $selector, $options['count']));
    }
  }
  
  /**
   * Tests whether or not a given string is in the response.
   *
   * @param string Text to check
   *
   * @return sfTestFunctionalBase|sfTester
   */
  public function responseContains($text)
  {
    $this->assertRegExp('/'.preg_quote($text, '/').'/', $this->response, sprintf('response contains "%s"', substr($text, 0, 40)));   
  }
  
  /**
   * Outputs some debug information about the current response.
   */
  public function debug($selector = null)
  {
    if (!is_null($selector))
    {
      echo "\n".$this->domCssSelector->matchSingle($selector)->getValue()."\n";
      $this->fail('Response debug');
    }
    
    printf("HTTP/1.X %s\n", $this->browser->getResponseCode());

    foreach ($this->browser->getResponseHeaders() as $name => $value)
    {
      printf("%s: %s\n", $name, $value);
    }

    echo "\n".$this->response."\n";
    
    $this->fail('Response debug');
  }
}