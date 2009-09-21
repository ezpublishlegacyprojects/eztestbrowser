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
    $fixture = dirname(__FILE__) . '/fixtures/objects.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($fixture);

    $object = new idObject();
    $object->fromeZContentObject(eZContentObject::fetchByRemoteID('test'));

    $this->assertEquals('article', $object->class_identifier);
    $this->assertEquals('Test 2', $object->title->__toString());
    $this->assertEquals(2, $object->section_id);
    $this->assertEquals(2, $object->object->mainParentNodeId());

    $translations_list =  $object->object->currentVersion()->translationList();

    $this->assertEquals(count($translations_list), 2);
    $this->assertEquals($translations_list[0]->attribute('language_code'), 'eng-GB');
    $this->assertEquals($translations_list[1]->attribute('language_code'), 'ita-IT');
  }

  public function testLoadClassesData()
  {
    $fixture = dirname(__FILE__) . '/fixtures/classes.yml';

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
    $classes = dirname(__FILE__) . '/fixtures/classes.yml';
    $objects = dirname(__FILE__) . '/fixtures/tree.yml';

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

//    $this->assertEquals('Notizia 3', $childrens[0]->getName('ita-IT'));
//    $this->assertEquals('Notizia 2', $childrens[1]->getName('ita-IT'));
//    $this->assertEquals('Notizia 1', $childrens[2]->getName('ita-IT'));

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
    $objects = dirname(__FILE__) . '/fixtures/related_object.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $article = eZContentObject::fetchByRemoteID('la_famiglia')->mainNode();

    $attributes = $article->datamap();
    $this->assertEquals(57, $attributes['image']->attribute('data_int'));
    $this->assertEquals(57, $attributes['image']->language('ita-IT')->attribute('data_int'));
  }

  public function testLoadContentRootData()
  {
    $objects = dirname(__FILE__) . '/fixtures/content_root.yml';

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
    $objects = dirname(__FILE__) . '/fixtures/multiple_locations_exceptions2.yml';
    
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
    $objects = dirname(__FILE__) . '/fixtures/multiple_locations.yml';

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
    $objects = dirname(__FILE__) . '/fixtures/priority.yml';

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
    $objects = dirname(__FILE__) . '/fixtures/related.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $object = eZContentObject::fetchByRemoteID('article');
    $related_object = $object->attribute('related_contentobject_array');
    $this->assertTrue(is_array($related_object));
    $this->assertEquals(count($related_object), 1);
  }

  public function testOrderedLoader()
  {
    $objects = dirname(__FILE__) . '/fixtures/ordered_loader.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $object = eZContentObject::fetchByRemoteID('folder1');
    $this->assertTrue($object instanceof eZContentObject);

    $object = eZContentObject::fetchByRemoteID('folder2');
    $this->assertTrue($object instanceof eZContentObject);
  }

  public function testLoadObjectRelationList()
  {
    $classes = dirname(__FILE__) . '/fixtures/classes.yml';
    $objects = dirname(__FILE__) . '/fixtures/objectrelationlist.yml';

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
    $objects = dirname(__FILE__) . '/fixtures/xmltext.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $object = eZContentObject::fetchByRemoteID('embed');
    $data_map = $object->dataMap();
    $content = $data_map['intro']->content();
    $this->assertTrue((bool)strstr($content->attribute('xml_data'), 'object_id="57"'));
  }

  public function testAddLocation()
  {
    $objects = dirname(__FILE__) . '/fixtures/addlocation.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $object = eZContentObject::fetchByRemoteID('article');
    $this->assertEquals(count($object->parentNodeIDArray()), 2);

  }

  public function testAddUrlAlias()
  {
    $objects = dirname(__FILE__) . '/fixtures/addurlalias.yml';

    $data = new ezpYamlData();
    $data->loadObjectsData($objects);

    $object = eZContentObject::fetchByRemoteID('article');
    $node = $object->mainNode();
    $url_alias = eZURLAliasML::fetchByAction('eznode', $node->attribute('node_id'));
    $this->assertEquals(count($url_alias), 3);
    $this->assertEquals($url_alias[0]->attribute('text'), 'An-article');
    $this->assertEquals($url_alias[1]->attribute('text'), 'alias-1');
    $this->assertEquals($url_alias[1]->attribute('lang_mask'), 2);
    $this->assertEquals($url_alias[2]->attribute('text'), 'alias-2');
  }

}