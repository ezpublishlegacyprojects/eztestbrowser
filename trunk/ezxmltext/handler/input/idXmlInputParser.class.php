<?php

class idXmlInputParser extends eZSimplifiedXMLInputParser
{
  protected $repository = '/tmp';
  protected $errors = array();

  /**
     * Constructor
     * For more info see {@link eZOEInputParser::eZXMLInputParser()}
     *
     * @param int $validateErrorLevel
     * @param int $detectErrorLevel
     * @param bool $parseLineBreaks flag if line breaks should be given meaning or not
     * @param bool $removeDefaultAttrs singal if attributes of default value should not be saved.
     */
    public function __construct( $validateErrorLevel = eZXMLInputParser::ERROR_NONE, $detectErrorLevel = eZXMLInputParser::ERROR_NONE,
                                 $parseLineBreaks = false, $removeDefaultAttrs = false )
    {
        $this->InputTags['img'] = array( 'nameHandler' => 'tagNameImg', 'noChildren' => true );
        $this->eZSimplifiedXMLInputParser( $validateErrorLevel, $detectErrorLevel, $parseLineBreaks, $removeDefaultAttrs );
    }

    public function setRepository($repository)
    {
      $this->repository = $repository;
    }

    public function tagNameImg($tagName, &$attributes)
    {
      if (!isset($attributes['src']))
      {
        throw new Exception('src attribute is mandatory');
      }

      try
      {
        $attributes['src'] = $this->cleanSrc($attributes['src']);
        $image = new idObject();
        $image->fromeZContentObject(eZContentObject::fetchByRemoteID($this->retrieveImageRemoteId($attributes['src'])));
      }
      catch(Exception $e)
      {
        if (!file_exists($this->getImageRepositoryPath($attributes['src'])))
        {
          throw new Exception('File '.$this->getImageRepositoryPath($attributes['src']).' does not exists');
        }
        
        $content_ini = eZINI::instance('content.ini');
        $content_ini->variable('NodeSettings', 'MediaRoot');

        $node = eZContentObjectTreeNode::fetchByUrlPath('media/images');

        $image = new idObject('image', $node->attribute('node_id'));
        $image->name = pathinfo($attributes['src'], PATHINFO_FILENAME);
        $image->image->fromString($this->getImageRepositoryPath($attributes['src']));
        $image->setAttribute('remote_id', $this->retrieveImageRemoteId($attributes['src']));
        $image->store();
        $image->publish();
      }

      $attributes['view'] = 'embed-inline';
      $attributes['href'] = 'ezobject://'.$image->id;
      unset($attributes['src']);
      
      return 'embed';
    }

    private function getImageRepositoryPath($image)
    {
      return $this->repository.'/'.$image;
    }

    public static function retrieveImageRemoteId($image)
    {
      return strtr($image, array('/' => '_'));
    }

    public static function cleanSrc($path)
    {
      return strtr($path, array('\\' => '/'));
    }

    public function callOutputHandler( $handlerName, $element, &$params )
    {
        $result = null;
        $thisOutputTag = $this->OutputTags[$element->nodeName];
        if ( isset( $thisOutputTag[$handlerName] ) )
        {
            if ( is_callable( array( $this, $thisOutputTag[$handlerName] ) ) )
            {
              try
              {
                $result = call_user_func_array( array( $this, $thisOutputTag[$handlerName] ),
                                                array( $element, &$params ) );
              }
              catch (Exception $e)
              {
                $this->errors[] = $e->getMessage();
              }
            }
            else
            {
                eZDebug::writeWarning( "'$handlerName' output handler for tag <$element->nodeName> doesn't exist: '" . $thisOutputTag[$handlerName] . "'.", 'eZXML input parser' );
            }
        }

        return $result;
    }

    public function callInputHandler( $handlerName, $tagName, &$attributes )
    {
        $result = null;
        $thisInputTag = $this->InputTags[$tagName];
        if ( isset( $thisInputTag[$handlerName] ) )
        {
            if ( is_callable( array( $this, $thisInputTag[$handlerName] ) ) )
            {
              try
              {
                $result = call_user_func_array( array( $this, $thisInputTag[$handlerName] ),
                                                array( $tagName, &$attributes ) );
              }
              catch (Exception $e)
              {
                $this->errors[] = $e->getMessage();
              }
            }
            else
            {
                eZDebug::writeWarning( "'$handlerName' input handler for tag <$tagName> doesn't exist: '" . $thisInputTag[$handlerName] . "'.", 'eZXML input parser' );
            }
        }
        return $result;
    }

    public function getErrors()
    {
      return $this->errors;
    }

    public function performPass1(&$data)
    {

      $data = self::cleanHtml($data);
      return parent::performPass1($data);
    }

    protected function stripTagAttribute($html_string, $attribute_name)
    {
      return preg_replace('| '.$attribute_name.'="[^"]*?"|m', '', $html_string);
    }

    protected function cleanHtml($string)
    {
      $string = $this->stripTagAttribute($string, 'align');
      $string = $this->stripTagAttribute($string, 'valign');
      $string = $this->stripTagAttribute($string, 'onclick');
      // $string = $this->stripTagAttribute($string, 'class');
      $string = $this->stripTagAttribute($string, 'width');
      $string = $this->stripTagAttribute($string, 'height');
      $string = $this->stripTagAttribute($string, 'style');
      $string = $this->stripTagAttribute($string, 'cellspacing');
      $string = $this->stripTagAttribute($string, 'cellpadding');
      $string = $this->stripTagAttribute($string, 'border');

      $string = str_replace('à', '&agrave;', $string);
      $string = str_replace('é', '&eacute;', $string);
      $string = str_replace('ù', '&ugrave;', $string);
      $string = str_replace('ì', '&igrave;', $string);
      

      $string = str_ireplace('<sup>', '<custom name="sup">', $string);
      $string = str_ireplace('</sup>', '</custom>', $string);
      $string = str_ireplace('<u>', '<em>', $string);
      $string = str_ireplace('</u>', '</em>', $string);
      $string = str_ireplace('<center>', '', $string);
      $string = str_ireplace('</center>', '', $string);
      $string = str_ireplace('<tbody>', '', $string);
      $string = str_ireplace('</tbody>', '', $string);
      $string = str_ireplace('<div>', '', $string);
      $string = str_ireplace('</div>', '', $string);
      $string = str_ireplace('<cr>', '', $string);
      $string = str_ireplace('</cr>', '', $string);
      $string = str_ireplace('<o:p></o:p>', '', $string);
      $string = str_ireplace('</strong><strong>', '', $string);

      $string = preg_replace('|<strong>\s*</strong>|im', '', $string);
      $string = preg_replace('|<b><i>(.*?)</b></i>|im', '<b><i>\1</i></b>', $string);
      $string = preg_replace('|<strong><i>(.*?)</strong></i>|im', '<b><i>\1</i></b>', $string);
      $string = preg_replace('|</?span[^>]*?>|m', '', $string);
      $string = preg_replace('|</?font[^>]*?>|m', '', $string);
      $string = str_ireplace('<br/ >', '<br />', $string);
      $string = preg_replace('|<p>([\s\n\r]*<br />[\s\n\r]*)*</p>|im', '', $string);
      $string = preg_replace('|href="javascript:window.open\(.([^\']*?)\'[^>]*?>|m', 'href="\1">', $string);
      $string = preg_replace('|href=\s+"|m', 'href="', $string);
      $string = preg_replace('|<a[^>]*?></a>|m', '', $string);

      $string = str_ireplace('’', "'", $string);
      $string = str_replace('è', '&egrave;', $string);
      $string = html_entity_decode($string, ENT_QUOTES);
      $string = str_ireplace('&rsquo;', "'", $string);

      $string = preg_replace('|<([0-9]+)|', '&lt;\1', $string);

      if (!mb_check_encoding($string,'UTF-8'))
      {
        $string = utf8_encode($string);
      }
      $string = str_replace('&bull;', '•', $string);
      

  //    echo "\n------------------------------------\n".$string;
      return $string;
    }
}

?>
