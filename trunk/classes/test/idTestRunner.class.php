<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of idTestRunnerclass
 *
 * @author michele
 */
class idTestRunner extends ezpTestRunner{

  public static function getSpecifiedConsoleOptions( $consoleInput )
  {
    $options = parent::getSpecifiedConsoleOptions($consoleInput);

    $options['junitLogfile'] = $options['xmlLogfile'];
    
    return $options;
  }

  public function runFromArguments()
  {
      self::$consoleInput = new ezcConsoleInput();

      self::registerConsoleArguments( self::$consoleInput );
      self::processConsoleArguments( self::$consoleInput );

      $options = self::getSpecifiedConsoleOptions( self::$consoleInput );
      $suite = $this->prepareTests( self::$consoleInput->getArguments(), $options );

      if ( self::$consoleInput->getOption( 'list-tests' )->value )
      {
          $this->listTests( $suite );
          exit( PHPUnit_TextUI_TestRunner::SUCCESS_EXIT );
      }

      if ( self::$consoleInput->getOption( 'list-groups' )->value )
      {
          $this->listGroups( $suite );
          exit( PHPUnit_TextUI_TestRunner::SUCCESS_EXIT );
      }

      try
      {
          $result = $this->doRun( $suite, $options );
      }
      catch ( ezcConsoleOptionException $e )
      {
          die ( $e->getMessage() . "\n" );
      }
  }
}