<?php

class idImportTest extends ezpDatabaseTestCase
{
  public function testImportImage()
  {
    $test1 = 'Lorem ipsum dolet <img src="fixtures/test.jpg" /> pippo';

    $object = new idObject('article', 2);
    $object->setImportImageRepository(dirname(__FILE__));
    $object->title = 'Questo è un test di un articolo';
    $object->body = $test1;
    $object->publish();

    $image = new idObject();
    $image->fromeZContentObject(eZContentObject::fetchByRemoteID(idXmlInputParser::retrieveImageRemoteId('fixtures/test.jpg')));
    
    $this->assertContains('<paragraph xmlns:tmp="http://ez.no/namespaces/ezpublish3/temporary/">Lorem ipsum dolet <embed view="embed-inline" object_id="'.$image->id.'"/> pippo</paragraph>', (string)$object->body);

    $object = new idObject('article', 2);
    $object->setImportImageRepository(dirname(__FILE__));
    $object->title = 'Questo è un test di un articolo';
    $object->body = $test1;
    $object->publish();

    $this->assertTrue(is_numeric($image->id));
    $this->assertContains('<paragraph xmlns:tmp="http://ez.no/namespaces/ezpublish3/temporary/">Lorem ipsum dolet <embed view="embed-inline" object_id="'.$image->id.'"/> pippo</paragraph>', (string)$object->body);
  }
}
?>
