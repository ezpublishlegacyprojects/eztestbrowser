<?php

class idObjectTest extends ezpDatabaseTestCase
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
  }
}

?>
