<?php
class ParseArguments
{
  public static function getSiteaccess(array $arguments)
  {
    foreach ($arguments as $argument)
    {
      preg_match('/^--site-access=(.*)/', $argument, $matches);
      if (isset($matches[1]))
      {
        return $matches[1];
      }
    }

    return false;
  }
}
?>
