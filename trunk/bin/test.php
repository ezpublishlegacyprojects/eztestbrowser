#!/usr/bin/env php
<?php

set_time_limit(0);

require_once 'autoload.php';
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

$script = eZScript::instance(array('description' => ("eZ Publish Test Runner\n\n" .
                                                         "sets up an eZ Publish testing environment" .
                                                         "\n"),
                                   'use-session' => false,
                                   'use-modules' => true,
                                   'site-access' => 'it',
                                   'use-extensions' => true));

$script->startup();
$script->initialize();

eZExecution::setCleanExit();

$console_input = new ezcConsoleInput();
$console_input->argumentDefinition = new ezcConsoleArguments();
$console_input->argumentDefinition[0] = new ezcConsoleArgument("test-name");
$console_input->argumentDefinition[0]->shorthelp = "File test name without .php extension";

$type_option = new ezcConsoleOption('t', 'test-type', ezcConsoleInput::TYPE_STRING, null, false);
$type_option->shorthelp = 'Test type: functional or unit';
$type_option->default = 'unit';
$console_input->registerOption($type_option);

$extension_option = new ezcConsoleOption('e', 'extension-name', ezcConsoleInput::TYPE_STRING, null, false);
$extension_option->shorthelp = 'Extension name where test is';
$extension_option->default = 'idwhitelabel';
$console_input->registerOption($extension_option);

$help_option = new ezcConsoleOption('h', 'help', ezcConsoleInput::TYPE_NONE);
$help_option->shorthelp = "Show this help menu";
$help_option->isHelpOption = true;
$console_input->registerOption($help_option);

try
{
  $console_input->process();

  if ($help_option->value === true)
  {
    echo $console_input->getHelpText("A simple text program");
    $script->shutdown(-1);
  }

}
catch (ezcConsoleOptionException $e)
{
 die($e->getMessage()."\n");
}

$runner = new PHPUnit_TextUI_TestRunner();

$test_file = 'extension'.DIRECTORY_SEPARATOR.$extension_option->value.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.$type_option->value.DIRECTORY_SEPARATOR.$console_input->argumentDefinition["test-name"]->value.'.php';
if (!file_exists($test_file))
{
  echo "Il file di test $test_file non esiste\n";
  $script->shutdown(-1);
}

$suite = $runner->getTest($console_input->argumentDefinition["test-name"]->value, realpath($test_file));
$result = $runner->doRun($suite);


$script->shutdown();
?>