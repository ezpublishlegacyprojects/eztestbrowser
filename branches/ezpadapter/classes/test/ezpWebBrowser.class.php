<?php

class ezpWebBrowser extends sfWebBrowser
{
  private function fixHeaders($headers)
  {
    $fixed_headers = array();
    foreach ($headers as $name => $value)
    {
      if (!preg_match('/([a-z]*)(-[a-z]*)*/i', $name))
      {
        $msg = sprintf('Invalid header "%s"', $name);
        throw new Exception($msg);
      }
      $fixed_headers[$this->normalizeHeaderName($name)] = trim($value);
    }

    return $fixed_headers;
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
  public function call($uri, $method = 'GET', $parameters = array(), $headers = array(), $changeStack = true)
  {
    $urlInfo = parse_url($uri);

    // Check headers
    $headers = $this->fixHeaders($headers);

    $this->initializeResponse();

    if ($changeStack)
    {
      $this->addToStack($uri, $method, $parameters, $headers);
    }

    $browser = $this->adapter->call($this, $uri, $method, $parameters, $headers);

    // redirect support
    if ((in_array($browser->getResponseCode(), array(301, 307)) && in_array($method, array('GET', 'HEAD'))) || in_array($browser->getResponseCode(), array(302,303)))
    {
      $this->call($browser->getResponseHeader('Location'), 'GET', array(), $headers);
    }

    return $browser;
  }

  public function followRedirect()
  { 
    $location = null;

    $nodes = $this->getResponseDomCssSelector()->matchSingle('meta[http-equiv="Location"]')->getNodes();

    if (isset($nodes[0]))
    {
      $location = $nodes[0]->getAttribute('content');
    }

    if (null === $location)
    {
      throw new Exception('The request was not redirected.');
    }

    return $this->get($location);
  }
}
