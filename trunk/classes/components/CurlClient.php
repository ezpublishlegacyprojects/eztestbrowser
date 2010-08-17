<?php

require_once('extension/eztestbrowser/classes/components/BrowserKit/Client.php');
require_once('extension/eztestbrowser/classes/components/BrowserKit/History.php');
require_once('extension/eztestbrowser/classes/components/BrowserKit/CookieJar.php');
require_once('extension/eztestbrowser/classes/components/BrowserKit/Request.php');
require_once('extension/eztestbrowser/classes/components/BrowserKit/Response.php');
require_once('extension/eztestbrowser/classes/components/DomCrawler/Crawler.php');
require_once('extension/eztestbrowser/classes/components/CssSelector/Parser.php');
require_once('extension/eztestbrowser/classes/components/CssSelector/Tokenizer.php');
require_once('extension/eztestbrowser/classes/components/CssSelector/TokenStream.php');
require_once('extension/eztestbrowser/classes/components/CssSelector/Token.php');
require_once('extension/eztestbrowser/classes/components/CssSelector/Node/NodeInterface.php');
require_once('extension/eztestbrowser/classes/components/CssSelector/Node/ElementNode.php');
require_once('extension/eztestbrowser/classes/components/CssSelector/Node/HashNode.php');
require_once('extension/eztestbrowser/classes/components/CssSelector/Node/AttribNode.php');
require_once('extension/eztestbrowser/classes/components/CssSelector/Node/CombinedSelectorNode.php');
require_once('extension/eztestbrowser/classes/components/CssSelector/XPathExpr.php');


use \Symfony\Components\BrowserKit\Client as Client;

/**
 * Description of eZClient
 *
 * @author cphp
 */
class CurlClient extends Client
{
  protected $curl;

  public function __construct(array $server = array(), History $history = null, CookieJar $cookieJar = null)
  {
    parent::__construct($server, $history, $cookieJar);
    $this->curl = curl_init();
  }

  protected function doRequest($request)
  {
    curl_setopt($this->curl, CURLOPT_URL, $request->getUri());
    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $request->getParameters());
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $request->getMethod());
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);

    $content = curl_exec($this->curl);

    $this->assertCurlError();

    $requestInfo = curl_getinfo($this->curl);

    return new \Symfony\Components\BrowserKit\Response($content, $requestInfo['http_code']);
  }

  public function assertCurlError()
  {
    if (curl_errno($this->curl))
    {
      throw new Exception(curl_error($this->curl));
    }
  }

  public function __destruct()
  {
    curl_close($this->curl);
  }
}

