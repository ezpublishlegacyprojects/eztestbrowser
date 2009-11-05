<?php

class idObjectTest extends idDatabaseTestCase
{
  public function testAttributeLanguage()
  {
    eZContentLanguage::fetchByLocale('ita-IT', true);

    $object = new idObject('article', 2);
    $object->title = 'New article test';
    $object->addTranslation('ita-IT', array('title' => 'Nuvo articolo di test'));
    $object->publish();
    
    $data_map = $object->datamap();
    
    $this->assertEquals($data_map['title']->language('ita-IT')->content(), 'Nuvo articolo di test');

    $this->assertEquals($object->title->__toString(), 'New article test');
    $this->assertEquals($object->title->ita_IT->__toString(), 'Nuvo articolo di test');
  }

  public function testAddLanguage()
  {
    eZContentLanguage::fetchByLocale('ita-IT', true);

    $object = new idObject('article', 2);
    $object->title = 'New article test';

    $this->assertEquals('New article test', $object->title->eng_GB->__toString());    

    $object->title->ita_IT = 'Nuovo articolo di test 2';
    $this->assertEquals('Nuovo articolo di test 2', $object->title->ita_IT->__toString());

    $object->body->ita_IT = '<embed view="embed" href="ezobject://88" /> <strong>Efficiente</strong> <br /> Non serve caricare immagini nel Database <br />';
    
    $this->assertContains('<section xmlns:image="http://ez.no/namespaces/ezpublish3/image/" xmlns:xhtml="http://ez.no/namespaces/ezpublish3/xhtml/" xmlns:custom="http://ez.no/namespaces/ezpublish3/custom/"><paragraph xmlns:tmp="http://ez.no/namespaces/ezpublish3/temporary/"><line><embed view="embed" object_id="88"/> <strong>Efficiente</strong> </line><line> Non serve caricare immagini nel Database </line></paragraph></section>', $object->body->ita_IT->__toString());
  }

  public function testFromArray()
  {
    $data = array('remote_id' => 'remote_id_from_array',
                  'title' => 'Article title');
    
    $object = new idObject('article', 2);
    $object->fromArray($data);
    $object->publish();

    $this->assertEquals('Article title', (string)$object->title);
    $this->assertEquals('remote_id_from_array', $object->remote_id);
  }

  public function testSpecialCharacters()
  {
    eZContentLanguage::fetchByLocale('ita-IT', true);

    $object = new idObject('article', 2);
    $object->title = 'New article special chars';
    $object->body = "’ &bull; è é ì ò à ù &egrave; &agrave;";

    $this->assertContains("’ • è é ì ò à ù", $object->body->__toString());
    //$this->assertEquals("’ • è é ì ò à ù è à", $object->body->__toString());

  }
}

?>
