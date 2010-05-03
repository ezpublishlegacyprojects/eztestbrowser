<?php
/**
 * Description of YamlClassclass
 *
 * @author Francesco (cphp) Trucchia <ft@ideato.it>
 */
class ExporterYamlClassTest extends idDatabaseTestCase
{
  public function testExportArticle()
  {
    $article_class = eZContentClass::fetchByIdentifier('article');

    $this->exporter = new ExporterYamlClass($article_class);
    $this->exporter->export();
    
    $classes = sfYaml::load($this->exporter->getOutput());
    $keys = array_keys($classes);
    $article = $classes['article'];
    
    $this->assertEquals('article', $keys[0]);
    $this->assertEquals('<short_title|title>', $article['object_name']);
    $this->assertEquals(true, $article['is_container']);

    $this->assertEquals(7, count($article['attributes']));
    $attributes_name = array_keys($article['attributes']);
    $this->assertEquals('title', $attributes_name[0]);
    $this->assertEquals('short_title', $attributes_name[1]);
    $this->assertEquals('author', $attributes_name[2]);
    $this->assertEquals('intro', $attributes_name[3]);
    $this->assertEquals('body', $attributes_name[4]);
    $this->assertEquals('enable_comments', $attributes_name[5]);
    $this->assertEquals('image', $attributes_name[6]);

    $title = $article['attributes']['title'];
    $this->assertEquals('Title', $title['name']);
    $this->assertEquals('ezstring', $title['type']);
    $this->assertTrue($title['is_required']);
    $this->assertFalse($title['is_information_collector']);
    $this->assertTrue($title['can_translate']);
  }

  public function testExportFolder()
  {
    $article_class = eZContentClass::fetchByIdentifier('folder');

    $this->exporter = new ExporterYamlClass($article_class);
    $this->exporter->export();

    $folder = sfYaml::load($this->exporter->getOutput());
    $keys = array_keys($folder);

    $this->assertEquals('folder', $keys[0]);
  }

  /**
   * @expectedException Exception
   */
  public function testException()
  {
    $article_class = eZContentClass::fetchByIdentifier('pluto');

    $this->exporter = new ExporterYamlClass($article_class);
  }
}

