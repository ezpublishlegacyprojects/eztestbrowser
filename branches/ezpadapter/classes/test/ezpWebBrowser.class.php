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

    $this->adapter->call($this, $uri, $method, $parameters, $headers);

    // redirect support
    if ((in_array($this->getResponseCode(), array(301, 307)) && in_array($method, array('GET', 'HEAD'))) || in_array($this->getResponseCode(), array(302,303)))
    {
      $this->call($this->getResponseHeader('Location'), 'GET', array(), $headers);
    }

    return $this;
  }

  /**
   * Follow redirect
   *
   * @return
   */
  public function followRedirect()
  { 
    $location = null;

    $nodes = $this->getResponseDomCssSelector()->matchSingle('meta[http-equiv="Location"]')->getNodes();

    if (isset($nodes[0]))
    {
      $location = $nodes[0]->getAttribute('content');
    }

    if (null === $location || $location = '')
    {
      throw new Exception('The request was not redirected.');
    }

    $this->get($location);

    return $this;
  }

  public function clickButton($name, $position = 0, $arguments = array())
  {
    if (!$dom = $this->getResponseDom())
    {
      throw new Exception('Cannot click because there is no current page in the browser');
    }

    $xpath = new DomXpath($dom);

    // form, the name being the button or input value
    if (!$form = $xpath->query(sprintf('//input[((@type="submit" or @type="button") and @value="%s") or (@type="image" and @alt="%s")]/ancestor::form', $name, $name))->item($position))
    {
      throw new Exception(sprintf('Cannot find the "%s" link or button.', $name));
    }

    // form attributes
    $url = $form->getAttribute('action');
    $method = $form->getAttribute('method') ? strtolower($form->getAttribute('method')) : 'get';

    // merge form default values and arguments
    $defaults = array();
    foreach ($xpath->query('descendant::input | descendant::textarea | descendant::select', $form) as $element)
    {
      $elementName = $element->getAttribute('name');
      $nodeName    = $element->nodeName;
      $value       = null;
      if ($nodeName == 'input' && ($element->getAttribute('type') == 'checkbox' || $element->getAttribute('type') == 'radio'))
      {
        if ($element->getAttribute('checked'))
        {
          $value = $element->getAttribute('value');
        }
      }
      else if (
        $nodeName == 'input'
        &&
        (($element->getAttribute('type') != 'submit' && $element->getAttribute('type') != 'button') || $element->getAttribute('value') == $name)
        &&
        ($element->getAttribute('type') != 'image' || $element->getAttribute('alt') == $name)
      )
      {
        $value = $element->getAttribute('value');
      }
      else if ($nodeName == 'textarea')
      {
        $value = '';
        foreach ($element->childNodes as $el)
        {
          $value .= $dom->saveXML($el);
        }
      }
      else if ($nodeName == 'select')
      {
        if ($multiple = $element->hasAttribute('multiple'))
        {
          $elementName = str_replace('[]', '', $elementName);
          $value = array();
        }
        else
        {
          $value = null;
        }

        $found = false;
        foreach ($xpath->query('descendant::option', $element) as $option)
        {
          if ($option->getAttribute('selected'))
          {
            $found = true;
            if ($multiple)
            {
              $value[] = $option->getAttribute('value');
            }
            else
            {
              $value = $option->getAttribute('value');
            }
          }
        }

        // if no option is selected and if it is a simple select box, take the first option as the value
        if (!$found && !$multiple)
        {
          $value = $xpath->query('descendant::option', $element)->item(0)->getAttribute('value');
        }
      }

      if (null !== $value)
      {
        $this->parseArgumentAsArray($elementName, $value, $defaults);
      }
    }

    // create request parameters
    $arguments = sfToolkit::arrayDeepMerge($defaults, $this->fields, $arguments);
    if ('post' == $method)
    {
      return $this->post($url, $arguments);
    }
    else
    {
      return $this->get($url, $arguments);
    }
  }
}
