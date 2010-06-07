<?php
/**
 * Description of HarmonyBugsWithItaLoaderTest
 *
 * @author filo
 */
class HarmonyBugsWithItaLoaderTest extends eZBrowserTestCase
{
  protected $verbose = true;
  
  /**
   * Sets sql and classes fixtures
   */
  public function fixturesSetUp()
  {
    $this->fixtures_classes = array();
    $this->fixtures_classes[] = 'extension/eztestbrowser/tests/bugs/fixtures/regalo_class.yml';

    $this->fixtures_objects = 'extension/eztestbrowser/tests/bugs/fixtures/shops.yml';
  }

  public function test_bugs_on_load_ita_fixtures()
  {
    
  }
}