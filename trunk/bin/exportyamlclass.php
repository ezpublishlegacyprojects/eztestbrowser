<?php

require_once 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => ( "Ideato Test Runner for eZ Publish\n\n" .
                                                         "sets up an eZ Publish testing environment" .
                                                         "\n" ),
                                      'use-session' => false,
                                      'use-modules' => true,
                                      'use-extensions' => true ) );

$script->startup();
$options = $script->getOptions('[class_identifier:]',
                               '',
                               array('class_identifier'  => 'Class identifier'));

$script->initialize();

$article_class = eZContentClass::fetchByIdentifier($options['class_identifier']);

$exporter = new ExporterYamlClass($article_class);
$exporter->export();

echo $exporter->getOutput();

$script->shutdown();