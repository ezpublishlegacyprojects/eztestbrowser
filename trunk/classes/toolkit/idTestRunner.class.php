<?php 

class idTestRunner extends ezpTestRunner 
{
  public static function main()
  {
    $testRunner = new idTestRunner();
    $testRunner->runFromArguments();
  }
    
  public function runFromArguments()
  {
    self::$consoleInput = new ezcConsoleInput();

    self::registerConsoleArguments(self::$consoleInput);
    parent::processConsoleArguments(self::$consoleInput);

    $options = self::getSpecifiedConsoleOptions(self::$consoleInput);
    $suite = $this->prepareTests(self::$consoleInput->getArguments(), $options);

    if (self::$consoleInput->getOption('list-tests')->value)
    {
      $this->listTests( $suite );
      exit( PHPUnit_TextUI_TestRunner::SUCCESS_EXIT );
    }

    if (self::$consoleInput->getOption( 'list-groups' )->value)
    {
      $this->listGroups($suite);
      exit(PHPUnit_TextUI_TestRunner::SUCCESS_EXIT);
    }

    try
    {
      $result = $this->doRun($suite, $options);
    }
    catch (ezcConsoleOptionException $e)
    {
      die($e->getMessage() . "\n");
    }
  }
    
  protected static function registerConsoleArguments($consoleInput)
  {
    parent::registerConsoleArguments($consoleInput);
  
    $testdox_html = new ezcConsoleOption( '', 'testdox-html', ezcConsoleInput::TYPE_STRING);
    $testdox_html->shorthelp = "Write agile documentation in HTML format to file.";
    $consoleInput->registerOption($testdox_html);
    
    $testdox_text = new ezcConsoleOption( '', 'testdox-text', ezcConsoleInput::TYPE_STRING);
    $testdox_text->shorthelp = "Write agile documentation in HTML format to file.";
    $consoleInput->registerOption($testdox_text);
  }
  
  /**
     * Returns an array of all the specified console options
     *
     * @param ezcConsoleInput $consoleInput
     */
    protected static function getSpecifiedConsoleOptions($consoleInput)
    {
        $options = parent::getSpecifiedConsoleOptions($consoleInput);
        
        $testdox_html = $consoleInput->getOption('testdox-html')->value;
        $options['storyHTMLFile'] = $testdox_html ? true : null;
        
        $testdox_text = $consoleInput->getOption('testdox-text')->value;
        $options['storyTextFile'] = $testdox_text ? true : null;

        return $options;
    }
}