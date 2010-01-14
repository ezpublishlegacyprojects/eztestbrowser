<?php

class ezpYamlDataTest extends idDatabaseTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->setName('ezpYamlDataTest Tests');
  }

  public function testLoadObjectsData()
  {
    $fixture = dirname(__FILE__) . '/../fixtures/objects.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($fixture);

    $object = new idObject();
    $object->fromeZContentObject(eZContentObject::fetchByRemoteID('test'));

    $this->assertEquals('article', $object->class_identifier);
    $this->assertEquals('Test 2', (string)$object->title);
    $this->assertEquals(2, $object->section_id);
    $this->assertEquals(2, $object->getObject()->mainParentNodeId());

    $translations_list =  $object->getObject()->currentVersion()->translationList();

    $this->assertEquals(count($translations_list), 2);
    $this->assertEquals($translations_list[0]->attribute('language_code'), 'eng-GB');
    $this->assertEquals($translations_list[1]->attribute('language_code'), 'ita-IT');
  }

  public function testLoadClassesData()
  {
    $fixture = dirname(__FILE__) . '/../fixtures/classes.yml';

    $data = new ezpYamlData();
    $data->loadClassesData($fixture);

    // testare se la classe esiste
    $class = eZContentClass::fetchByIdentifier('news');

    $this->assertEquals($class->name(), 'News');
    $this->assertEquals($class->name('ita-IT'), 'Notizia');

    $this->assertEquals($class->attribute('contentobject_name'), '<title>');
    $this->assertTrue((bool)$class->attribute('is_container'));
    $this->assertTrue($class->inGroup('1'));

    $attributes = $class->dataMap();

    $this->assertEquals($attributes['title']->name(), 'Title');
    $this->assertEquals($attributes['title']->name('ita-IT'), 'Titolo');
    $this->assertEquals($attributes['title']->attribute('data_type_string'), 'ezstring');
    $this->assertTrue((bool)$attributes['title']->attribute('is_required'));
    $this->assertTrue((bool)$attributes['title']->attribute('is_information_collector'));

    $this->assertEquals($attributes['teaser']->name(), 'Teaser');
    $this->assertEquals($attributes['teaser']->name('ita-IT'), 'Intro');
    $this->assertEquals($attributes['teaser']->attribute('data_type_string'), 'ezxmltext');
    $this->assertTrue((bool)$attributes['teaser']->attribute('is_required'));

    $this->assertEquals($attributes['body']->name(), 'Body');
    $this->assertEquals($attributes['body']->name('ita-IT'), 'Corpo');
    $this->assertEquals($attributes['body']->attribute('data_type_string'), 'ezxmltext');
    $this->assertFalse((bool)$attributes['body']->attribute('is_required'));

    $this->assertFalse((bool)$attributes['date']->attribute('is_required'));
  }

  public function testLoadNodeTreeData()
  {
    $classes = dirname(__FILE__) . '/../fixtures/classes.yml';
    $objects = dirname(__FILE__) . '/../fixtures/tree.yml';

    $data = new ezpYamlData();
    $data->loadClassesData($classes);
    $data->loadObjectsData($objects);

    $folder_news = eZContentObject::fetchByRemoteID('folder_news')->mainNode();
    $childrens = $folder_news->children();

    $this->assertEquals(count($childrens), 3);

    $attributes = $childrens[0]->datamap();
    $this->assertEquals('Notizia 3', $attributes['title']->language('ita-IT')->content());

    $attributes = $childrens[1]->datamap();
    $this->assertEquals('Notizia 2', $attributes['title']->language('ita-IT')->content());

    $attributes = $childrens[2]->datamap();
    $this->assertEquals('Notizia 1', $attributes['title']->language('ita-IT')->content());

    $attributes = $childrens[2]->datamap();
    $this->assertEquals(strtotime('today'), $attributes['publish_date']->attribute('data_int'));
    $this->assertEquals(date('m', strtotime('today')), $attributes['publish_date']->content()->month());

    $this->assertEquals(date('m', strtotime('today')), $attributes['publish_date']->language('ita-IT')->content()->month());
    $this->assertEquals(date('d', strtotime('today')), $attributes['publish_date']->language('ita-IT')->content()->day());

    $attributes = $childrens[1]->datamap();
    $this->assertEquals(date('m', strtotime('today')), $attributes['date']->language('ita-IT')->content()->month());
    $this->assertEquals(date('d', strtotime('today')), $attributes['date']->language('ita-IT')->content()->day());

  }

  public function testLoadRelatedObjectData()
  {
    $objects = dirname(__FILE__) . '/../fixtures/related_object.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $article = eZContentObject::fetchByRemoteID('la_famiglia')->mainNode();

    $attributes = $article->datamap();
    $this->assertEquals(57, $attributes['image']->attribute('data_int'));
    $this->assertEquals(57, $attributes['image']->language('ita-IT')->attribute('data_int'));
  }

  public function testLoadContentRootData()
  {
    $objects = dirname(__FILE__) . '/../fixtures/content_root.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $homepage = eZContentObject::fetchByRemoteID('homepage');
    eZContentObject::clearCache(array($homepage->attribute('id')));

    $this->assertEquals('Home Page', $homepage->name());

    $attributes = $homepage->datamap();
    $this->assertEquals('Short description', trim(strip_tags($attributes['short_description']->content()->attribute('output')->attribute('output_text')), "\n"));
    $this->assertEquals('Description', trim(strip_tags($attributes['description']->content()->attribute('output')->attribute('output_text')), "\n"));
    $this->assertEquals('Descrizione breve', trim(strip_tags($attributes['short_description']->language('ita-IT')->content()->attribute('output')->attribute('output_text')), "\n"));
  }

  public function testLoadMultipleLocationDataExceptions()
  {
    $objects = dirname(__FILE__) . '/../fixtures/multiple_locations_exceptions2.yml';
    
    try
    {
      $data = new ezpYamlData();
      $data->loadObjectsData($objects);
      $this->fail('ezpYamlData didnt\'t raise an exception');
    }
    catch (Exception $e)
    {}
  }

  public function testLoadMultipleLocationData()
  {
    $objects = dirname(__FILE__) . '/../fixtures/multiple_locations.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $article = eZContentObject::fetchByRemoteID('la_famiglia');
    eZContentObject::clearCache(array($article->attribute('id')));
    $assigned_nodes = $article->attribute('assigned_nodes');

    $this->assertEquals(59, $assigned_nodes[0]->attribute('parent_node_id'));
    $this->assertEquals(60, $assigned_nodes[1]->attribute('parent_node_id'));
    $this->assertEquals(2, $assigned_nodes[2]->attribute('parent_node_id'));
    $this->assertTrue($assigned_nodes[2]->isMain());

    $menu_top = eZContentObject::fetchByRemoteID('folder_menu_top');
    $this->assertEquals(3, $menu_top->mainNode()->childrenCount());

    $menu_left = eZContentObject::fetchByRemoteID('folder_menu_left');
    $this->assertEquals(2, $menu_left->mainNode()->childrenCount());

    $parameters = array('Language' => 'eng-GB');
    $childrens_count = eZContentObjectTreeNode::subTreeCountByNodeID($parameters, $menu_top->mainNode()->attribute('node_id'));
    $this->assertEquals(3, $childrens_count);

    $parameters = array('Language' => 'ita-IT');
    $childrens_count = eZContentObjectTreeNode::subTreeCountByNodeID($parameters, $menu_top->mainNode()->attribute('node_id'));
    $this->assertEquals(1, $childrens_count);
  }

  public function testLoadPriorityData()
  {
    $objects = dirname(__FILE__) . '/../fixtures/priority.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $article = eZContentObject::fetchByRemoteID('la_famiglia');
    $this->assertEquals(10, $article->mainNode()->attribute('priority'));

    $assigned_nodes = $article->attribute('assigned_nodes');
    $this->assertEquals(10, $assigned_nodes[1]->attribute('priority'));
    $this->assertEquals(-1, $assigned_nodes[0]->attribute('priority'));
  }

  public function testLoadRelatedObjects()
  {
    $objects = dirname(__FILE__) . '/../fixtures/related.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $object = eZContentObject::fetchByRemoteID('article');
    $related_object = $object->attribute('related_contentobject_array');
    $this->assertTrue(is_array($related_object));
    $this->assertEquals(count($related_object), 1);
  }

  public function testOrderedLoader()
  {
    $objects = dirname(__FILE__) . '/../fixtures/ordered_loader.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $object = eZContentObject::fetchByRemoteID('folder1');
    $this->assertTrue($object instanceof eZContentObject);

    $object = eZContentObject::fetchByRemoteID('folder2');
    $this->assertTrue($object instanceof eZContentObject);
  }

  public function testLoadObjectRelationList()
  {
    $classes = dirname(__FILE__) . '/../fixtures/classes.yml';
    $objects = dirname(__FILE__) . '/../fixtures/objectrelationlist.yml';

    $data = new ezpYamlData();
    $data->loadClassesData($classes);
    $data->loadObjectsData($objects);

    $object = eZContentObject::fetchByRemoteID('related');
    $data_map = $object->dataMap();
    $content = $data_map['images']->content();
    $this->assertEquals(count($content['relation_list']), 2);

    $content_ita = $data_map['images']->language('ita-IT')->content();
    $this->assertEquals(count($content_ita['relation_list']), 2);

  }

  public function testLoadXMLText()
  {
    $objects = dirname(__FILE__) . '/../fixtures/xmltext.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $object = eZContentObject::fetchByRemoteID('embed');
    $data_map = $object->dataMap();
    $content = $data_map['intro']->content();
    $this->assertTrue((bool)strstr($content->attribute('xml_data'), 'object_id="57"'));
  }

  public function testAddLocation()
  {
    $objects = dirname(__FILE__) . '/../fixtures/addlocation.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $object = eZContentObject::fetchByRemoteID('article');
    $this->assertEquals(count($object->parentNodeIDArray()), 2);

  }

  public function testAddUrlAlias()
  {
    $objects = dirname(__FILE__) . '/../fixtures/addurlalias.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $object = eZContentObject::fetchByRemoteID('article');
    $node = $object->mainNode();
    $url_alias = eZURLAliasML::fetchByAction('eznode', $node->attribute('node_id'));
    $this->assertEquals(count($url_alias), 3);
    $this->assertEquals('An-article', $url_alias[0]->attribute('text'));
    $this->assertEquals('alias-1', $url_alias[1]->attribute('text'));
    $this->assertEquals(2, $url_alias[1]->attribute('lang_mask'));
    $this->assertEquals('alias-2', $url_alias[2]->attribute('text'));
  }

  public function testSwapTo()
  {
    $fixture = dirname(__FILE__) . '/../fixtures/classes.yml';
    $objects = dirname(__FILE__) . '/../fixtures/swap.yml';
    $data = new ezpYamlData();
    $data->loadClassesData($fixture);
    $data->loadObjectsData($objects);

    $object = eZContentObject::fetchByRemoteID('homepage');
    $node = $object->mainNode();
    $this->assertEquals($node->attribute('node_id'), 2);
  }

  public function testClassIdentifierAlias()
  {
    $fixture_objects = dirname(__FILE__) . '/../fixtures/multiple_objects.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($fixture_objects);

    $this->assertTrue(eZContentObject::fetchByRemoteID('folder1') instanceof eZContentObject);
    $this->assertTrue(eZContentObject::fetchByRemoteID('folder2') instanceof eZContentObject);
    $this->assertTrue(eZContentObject::fetchByRemoteID('folder3') instanceof eZContentObject);
    $this->assertTrue(eZContentObject::fetchByRemoteID('folder4') instanceof eZContentObject);
    $this->assertTrue(eZContentObject::fetchByRemoteID('folder5') instanceof eZContentObject);
    $this->assertTrue(eZContentObject::fetchByRemoteID('folder6') instanceof eZContentObject);
    $this->assertTrue(eZContentObject::fetchByRemoteID('folder7') instanceof eZContentObject);
    $this->assertTrue(eZContentObject::fetchByRemoteID('folder8') instanceof eZContentObject);
  }

  public function testModifyObjectAfterPublish()
  {
    $fixture_objects = dirname(__FILE__) . '/../fixtures/modify_object.yml';

    $data = new ezpYamlData(true);
    $data->loadObjectsData($fixture_objects);
    eZContentObject::clearCache();
    $folder = idObjectRepository::retrieveByRemoteId('folder');
    
    $this->assertEquals('Folder after change', (string)$folder->name);
    $this->assertContains('test english modified', (string)$folder->description);
    $this->assertContains('test italiano modificato', (string)$folder->description->ita_IT);
  }

  public function testImportAllDatatypes()
  {
    $fixture = dirname(__FILE__) . '/../fixtures/all_datatypes.classes.yml';
    $objects = dirname(__FILE__) . '/../fixtures/all_datatypes.objects.yml';

    $data = new ezpYamlData();
    $data->loadClassesData($fixture);
    $data->loadObjectsData($objects);

    $object = idObjectRepository::retrieveByRemoteId('object');

    $this->assertEquals(strtotime('05/31/1979'), (string)$object->ezdate);
    $this->assertEquals(strtotime('05/31/1979'), (string)$object->ezdate->eng_GB);
    $this->assertEquals(strtotime('05/31/2000'), (string)$object->ezdate->ita_IT);

    $this->assertEquals(strtotime('05/31/1979 23:15'), (string)$object->ezdatetime);
    $this->assertEquals(strtotime('05/31/1979 23:15'), (string)$object->ezdatetime->eng_GB);
    $this->assertEquals(strtotime('05/31/2000 23:15'), (string)$object->ezdatetime->ita_IT);

    $this->assertEquals('pluto@example.com', (string)$object->ezemail);
    $this->assertEquals('pluto@example.com', (string)$object->ezemail->eng_GB);
    $this->assertEquals('pluto@example.it', (string)$object->ezemail->ita_IT);

    $this->assertContains('test.gif', (string)$object->ezbinaryfile);
    $this->assertContains('test.gif', (string)$object->ezbinaryfile->eng_GB);
    $this->assertContains('test.gif', (string)$object->ezbinaryfile->ita_IT);
    
    $this->assertEquals(1, (string)$object->ezboolean);
    $this->assertEquals(1, (string)$object->ezboolean->eng_GB);
    $this->assertEquals(0, (string)$object->ezboolean->ita_IT);

    $this->assertContains('pippolone.gif', (string)$object->ezimage);
    $this->assertContains('pippolone.gif', (string)$object->ezimage->eng_GB);
    $this->assertContains('pippo.gif', (string)$object->ezimage->ita_IT);

    $this->assertEquals(1234, (string)$object->ezinteger);
    $this->assertEquals(1234, (string)$object->ezinteger->eng_GB);
    $this->assertEquals(4321, (string)$object->ezinteger->ita_IT);

    $this->assertEquals('plutonio, paperopoli', (string)$object->ezkeyword);
    $this->assertEquals('plutonio, paperopoli', (string)$object->ezkeyword->eng_GB);
    $this->assertEquals('pluto, paperino, pippone', (string)$object->ezkeyword->ita_IT);

    $this->assertEquals("1234.56", (string)$object->ezfloat);
    $this->assertEquals("1234.56", (string)$object->ezfloat->eng_GB);
    $this->assertEquals("1234.56", (string)$object->ezfloat->ita_IT);

    $this->assertEquals(1, (string)$object->ezobjectrelation);
    $this->assertEquals(1, (string)$object->ezobjectrelation->eng_GB);
    $this->assertEquals(50, (string)$object->ezobjectrelation->ita_IT);

    $this->assertEquals("1-50", (string)$object->ezobjectrelationlist);
    $this->assertEquals("1-50", (string)$object->ezobjectrelationlist->eng_GB);
    $this->assertEquals("50-1", (string)$object->ezobjectrelationlist->ita_IT);
    
    $this->assertEquals('1', (string)$object->ezselection);
    $this->assertEquals('1', (string)$object->ezselection->eng_GB);
    $this->assertEquals('2', (string)$object->ezselection->ita_IT);

    $this->assertEquals('pippolone', (string)$object->ezstring);
    $this->assertEquals('pippolone', (string)$object->ezstring->eng_GB);
    $this->assertEquals('pippo', (string)$object->ezstring->ita_IT);

    $this->assertEquals('this is an english text', (string)$object->eztext);
    $this->assertEquals('this is an english text', (string)$object->eztext->eng_GB);
    $this->assertEquals('questo è un test in italiano', (string)$object->eztext->ita_IT);

    $this->assertEquals('12:30:0', (string)$object->eztime);
    $this->assertEquals('12:30:0', (string)$object->eztime->eng_GB);
    $this->assertEquals('0:30:0', (string)$object->eztime->ita_IT);

    $this->assertEquals('http://www.google.com|Google.com', (string)$object->ezurl);
    $this->assertEquals('http://www.google.com|Google.com', (string)$object->ezurl->eng_GB);
    $this->assertEquals('http://www.google.it|Google.it', (string)$object->ezurl->ita_IT);
    
    $this->assertEquals('pippo|pippo@example.com|passwd_en|md5_password', (string)$object->ezuser);
    $this->assertEquals('pippo|pippo@example.com|passwd_en|md5_password', (string)$object->ezuser->eng_GB);
    $this->assertEquals('pippo|pippo@example.com|passwd_en|md5_password', (string)$object->ezuser->ita_IT);
    
    $this->assertContains('<strong>This</strong> is a <emphasize>description</emphasize>', (string)$object->ezxmltext);
    $this->assertContains('<strong>This</strong> is a <emphasize>description</emphasize>', (string)$object->ezxmltext->eng_GB);
    $this->assertContains('<strong>Questa</strong> è una <emphasize>descrizione</emphasize>', (string)$object->ezxmltext->ita_IT);
  }
}