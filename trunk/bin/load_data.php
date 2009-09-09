<?php
// SOFTWARE NAME: eZ Deploy
// SOFTWARE RELEASE: 0.1
// COPYRIGHT NOTICE: Copyright (C) 2008 idaeto srl
// SOFTWARE LICENSE: GNU General Public License v3.0
// AUTHOR: Francesco (cphp) Trucchia - ft@ideato.it
// NOTICE: >
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, either version 3 of the License, or
//  (at your option) any later version.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

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

$data = new ezpYamlData();

if ($options['classes_data'])
{
  $data->loadClassesData($options['classes_data']);
}

if ($options['objects_data'])
{
  $data->loadObjectsData($options['objects_data']);
}

$cli->output( "Loading completed" );
return $script->shutdown();


?>
