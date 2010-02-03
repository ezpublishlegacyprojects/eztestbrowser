<?php


class loadDatabaseTest extends eZBrowserTestCase
{
  protected $verbose = true;
  protected $load_database = true;
  protected $directory_log_mail_path = "";

  protected function initialize()
  {
    parent::initialize();
    $this->sqlFiles = array(realpath('extension/eztestbrowser/tests/unit/fixtures/sql/harmony_dev_ez.sql'));
  }

  protected function insertSqlRawData()
  {
    $dsn = ezpTestRunner::dsn();
    if (!isset($dsn->parts['database']) || !isset($dsn->parts['username']) || !isset($dsn->parts['password']))
    {
      return false;
    }

    $database = $dsn->parts['database'];
    $user = $dsn->parts['username'];
    $password = $dsn->parts['password'];

    foreach ($this->sqlFiles as $sql_file)
    {
      shell_exec('mysql -u '.$user.' --password="'.$password.'" '.$database.' < '.$sql_file);
    }
    
    return true;
  }

  protected function insertSql()
  {
    if (!$this->insertSqlRawData())
    {
      throw new Exception('Impossible to load some sql files');
    }
  }

  protected function fixturesSetup(){}
  
  public function testDatabaseLoded()
  {
    $user = eZUser::fetchByEmail('francesco@ideato.it');
    $this->assertEquals('admin', $user->Login);
  }
}
?>