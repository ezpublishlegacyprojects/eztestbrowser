<?php

class eZBrowserTestCase extends BrowserTestCase
{
  protected $sqlFiles = array();

  protected $backupGlobals = false;

  protected static $load_once = false;
  
  /**
   * Sets up the database enviroment
   */
  protected function setUp()
  {
    if(!self::$load_once/* || ezpTestRunner::dbPerTest()*/)
    {
      self::$load_once = true;
      
      $ini = eZINI::instance('site.ini');
      $this->sqlFiles = array(realpath('kernel/sql/mysql/kernel_schema.sql'), realpath('kernel/sql/mysql/cleandata.sql'));

      if (!$ini->hasVariable('DatabaseSettings', 'dsn'))
      {
        throw new Exception('Database dsn is not set in DatabaseSettings on site.ini file');
      }

      $dsn = new ezpDsn($ini->variable('DatabaseSettings', 'dsn'));
      $this->sharedFixture = ezpTestDatabaseHelper::create($dsn);
      ezpTestDatabaseHelper::insertSqlData( $this->sharedFixture, $this->sqlFiles );
      eZDB::setInstance( $this->sharedFixture );

      $ini_test = eZINI::instance('test.ini');

      if (!$ini_test->hasVariable('TestSettings', 'Objects') || !$ini_test->hasVariable('TestSettings', 'Classes'))
      {
        throw new Exception('The fixtures files is not configured');
      }

      $classes_fixtures = realpath($ini_test->variable('TestSettings', 'Classes'));
      $objects_fixtures = realpath($ini_test->variable('TestSettings', 'Objects'));

      if (!file_exists($classes_fixtures))
      {
        throw new Exception("The objects fixtures file $classes_fixtures does not exists");
      }

      if (!file_exists($objects_fixtures))
      {
        throw new Exception("The objects fixtures file $objects_fixtures does not exists");
      }

      $data = new ezpYamlData();
      $data->loadClassesData($classes_fixtures);
      $data->loadObjectsData($objects_fixtures);

      $this->charset = $GLOBALS['eZTextCodecInternalCharsetReal'];
      $GLOBALS['eZTextCodecInternalCharsetReal'] = 'utf-8';
    }
    eZDB::setInstance($this->sharedFixture);
  }

  public function listUsedTemplates()
  {
    $nodes = $this->domCssSelector->matchAll('table#templateusage tr td');
    $values = $nodes->getValues();

    for ($i = 0; $i <= count($values); $i++)
    {
        if ($i % 6 == 0) echo "\n";
        echo $values[$i]."\t";
    }
  }

  public function tearDown()
  {
    unset($GLOBALS['eZContentLanguageList']);
    unset($GLOBALS['eZContentLanguageMask']);

  }
}