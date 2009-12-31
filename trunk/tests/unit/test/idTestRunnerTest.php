<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of idTestRunnerTest
 *
 * @author michele
 */
class idTestRunnerTest extends PHPUnit_Framework_TestCase
{
  public function testGetSpecifiedConsoleOptions()
  {
    $console = $this->getMock('stdClass', array('getOption'));

    $ret = new stdClass();
    $ret->value = 'foo';

    $console->expects($this->any())
             ->method('getOption')
             ->will($this->returnValue($ret));

    $options = idTestRunner::getSpecifiedConsoleOptions($console);

    $this->assertTrue(isset($options['junitLogfile']));
    $this->assertEquals($options['junitLogfile'], $options['xmlLogfile']);
  }
}
?>
