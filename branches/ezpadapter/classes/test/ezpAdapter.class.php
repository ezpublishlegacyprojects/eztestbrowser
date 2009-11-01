<?php

class ezpAdapter
{
  protected
    $options             = array(),
    $browser             = null;
    
  public function __construct($options = array())
  {
    $this->options = $options;     
  }
    
  /**
   * Submits a request
   *
   * @param string  The request uri
   * @param string  The request method
   * @param array   The request parameters (associative array)
   * @param array   The request headers (associative array)
   * @param boolean To specify is the request changes the browser history
   *
   * @return sfWebBrowser The current browser object
   */  
  public function call($browser, $uri, $method = 'GET', $parameters = array(), $headers = array())
  { 
    $url_info = parse_url($uri);    
    
    $_SERVER['REQUEST_URI'] = isset($url_info['path']) ? $url_info['path'] : $uri;

    $_SERVER['HTTP_HOST'] = null;
    
    if (isset($this->options['host']))
    {
      $_SERVER['HTTP_HOST'] = $this->options['host'];
    }

    if (isset($url_info['host']))
    {
      $_SERVER['HTTP_HOST'] = $url_info['host'];
    }

    $_SERVER['SERVER_NAME']     = $_SERVER['HTTP_HOST'];
    $_SERVER['HTTP_USER_AGENT'] = 'ezpAdapter';
    $_SERVER['DOCUMENT_ROOT']   = dirname(__FILE__.'/../../../../../');
    $_SERVER['SCRIPT_FILENAME'] = dirname(__FILE__.'/../../../../../').'index.php';
    $_SERVER['SCRIPT_NAME']     = '/index.php';
    $_SERVER['SERVER_PORT']     = isset($url_info['port']) ? $url_info['port'] :'80';
    $_SERVER["SERVER_PROTOCOL"] = "HTTP/1.1";
    $_SERVER["REQUEST_METHOD"]  = $method;

    switch($method)
    {
      case 'GET':
        $_GET = $parameters;
        break;
      case 'POST':
        $_POST = $parameters;
        break;
    }
    
    
    $controller = new ezpController();
    $output = $controller->dispatch();

    $browser->setResponseCode(200);
    $browser->setResponseHeaders($this->getHeadersFromArray($controller->headerList));
    $browser->setResponseText($output);

    unset($controller);

    return $browser;
  }

  private function getHeadersFromArray($headers_array)
  {
    $headers = array();
    foreach($headers_array as $name => $value)
    {
      $headers[] = $name.': '.$value;
    }

    return $headers;
  }
  
}