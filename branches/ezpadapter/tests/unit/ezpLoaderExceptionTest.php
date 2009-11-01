<?php

class ezpLoaderExceptionTest extends idDatabaseTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->setName('ezpLoaderExceptionTest Tests');
  }

  public function testEmptyArrayException()
  {
    try
    {
      $data = new ezpLoader();
      $data->buildObject(array());
    }
    catch(ezpInvalidObjectException $e)
    {
      $this->assertEquals('Impossible to create or retrieve an object. You need to pass an id or a class_identifier.', $e->getMessage());
      return;
    }

    $this->fail('ezpLoader doesn\'t  throw in a ezpInvalidObjectException');
  }

  public function testWrongArrayException()
  {
    $fixture = array('Folder');

    try
    {
      $data = new ezpLoader();
      $data->buildObject($fixture);
    }
    catch(ezpInvalidObjectException $e)
    {
      $this->assertEquals('Impossible to create or retrieve an object. You need to pass an id or a class_identifier.', $e->getMessage());
      return;
    }

    $this->fail('ezpLoader doesn\'t  throw in a ezpInvalidObjectException');
  }

  public function testWrongClassIndentifierException()
  {
    $fixture = array('class_identifier' => 'FFolder');

    try
    {
      $data = new ezpLoader();
      $data->buildObject($fixture);
    }
    catch(ezpInvalidClassException $e)
    {
      $this->assertEquals('FFolder does not exists.', $e->getMessage());
      return;
    }

    $this->fail('ezpLoader doesn\'t  throw in a ezpInvalidClassException');
  }

}
