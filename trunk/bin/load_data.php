<?php
date_default_timezone_set('Europe/Rome');

require_once('autoload.php');

$cli =& eZCLI::instance();
$script =& eZScript::instance( array( 'description' => ( "Load data from yaml"),
                                      'use-session' => true,
                                      'use-modules' => true,
                                      'site-access' => 'dev',
                                      'use-extensions' => true) );

$script->startup();

$options = $script->getOptions( "[objects_data;][classes_data;]",
                                "",
                                array(	'objects_data'	=> 'Path to yaml object file',
                                        'classes_data'	=> 'Path to yaml classes file'));

$script->initialize();

/*$db = eZDB::instance();

ezpTestDatabaseHelper::clean(eZDB::instance());

$db->insertFile('kernel/sql/mysql/', 'kernel_schema.sql', false);
$db->insertFile('kernel/sql/mysql/', 'cleandata.sql', false);

$data = new ezpYamlData();

if ($options['classes_data'])
{
  $data->loadClassesData($options['classes_data']);
}

if ($options['objects_data'])
{
  $data->loadObjectsData($options['objects_data']);
}*/

$cli->output( "Loading completed" );
return $script->shutdown();


?>
