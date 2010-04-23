<?php
/**
* Copyright (C) 2009  Francesco trucchia
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.

* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*
* @author Francesco (cphp) Trucchia <ft@ideato.it>
*
*/

class ezpYamlData extends ezpLoader
{
  /*
   * Parse a yaml file and return the associative array
   * 
   * @param string $file
   * @return array
   */
  protected function parseYaml($file)
  {
    if (!file_exists($file))
    {
      throw new Exception('File '. $file .' does not exist');
    }
    
    return sfYaml::load($file);
  }

  /*
   * Parse yaml file and build ez objects
   *
   * @param string $file
   * @see loadObjectsDataFromYaml()
   */
  public function loadObjectsData($file)
  {
    $data = $this->parseYaml($file);
    $this->buildObjects($data);
  }

  /**
   * Clear class_identifier
   * @see parent::clearClassIdentifier
   *
   * @param string $object_class
   * @return string
   */
  protected function clearClassIdentifier($class_identifier)
  {
    return trim(str_replace(intval($class_identifier), '', $class_identifier), '_');
  }

  /**
   * Load classes data from yaml file
   *
   * @param string $file
   */
  public function loadClassesData($files)
  {
    if (!is_array($files)) $files = array($files);

    foreach($files as $yaml)
    {
      $data = $this->parseYaml($yaml);
      $this->buildClasses($data);
    }
  }

}

