<?php

class eZAdapter
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
    
    echo $url_info['path'];
    
    $output = shell_exec('php index_test.php '.$url_info['path']);
    
    //echo $output;
    $headers = unserialize(file_get_contents(realpath('var/cache/').'ezadapter-headerlist'));
    
    $browser->setResponseCode(200);
    $browser->setResponseHeaders($headers);
    $browser->setResponseText($output);
    
    return $browser;
  }
}