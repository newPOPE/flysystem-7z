<?php

class P7zAdapterTest extends \PHPUnit\Framework\TestCase {

  public function zipAdapterProvider () {
    return [
      [new \AdamStipak\Flysystem\Adapter\P7zAdapter(__DIR__ . '/WRIX.zip')],
    ];
  }

  /**
   * @dataProvider zipAdapterProvider
   */
  public function testListContents (\AdamStipak\Flysystem\Adapter\P7zAdapter $adapter) {
    $this->assertCount(67, $adapter->listContents());
  }

  /**
   * @dataProvider zipAdapterProvider
   */
  public function testGetSize (\AdamStipak\Flysystem\Adapter\P7zAdapter $adapter) {
    $this->assertEquals(75932, $adapter->getSize('WRIX/WRIX/WRIST RIGHT/SCOUT 3-PLANE RT. - 2/IM-0001-0001.dcm'));
  }

  /**
   * @dataProvider zipAdapterProvider
   */
  public function testRead (\AdamStipak\Flysystem\Adapter\P7zAdapter $adapter) {
    $crc = 'f0e91067';
    $this->assertEquals($crc, dechex(crc32($adapter->read('WRIX/WRIX/WRIST RIGHT/SCOUT 3-PLANE RT. - 2/IM-0001-0001.dcm'))));
  }

  /**
   * @dataProvider zipAdapterProvider
   */
  public function testMimetype (\AdamStipak\Flysystem\Adapter\P7zAdapter $adapter) {
    $this->assertEquals('application/dicom', $adapter->getMimetype('WRIX/WRIX/WRIST RIGHT/SCOUT 3-PLANE RT. - 2/IM-0001-0001.dcm'));
  }

  /**
   * @dataProvider zipAdapterProvider
   */
  public function testHas (\AdamStipak\Flysystem\Adapter\P7zAdapter $adapter) {
    $this->assertTrue($adapter->has('WRIX/WRIX/WRIST RIGHT/SCOUT 3-PLANE RT. - 2/IM-0001-0001.dcm'));
    $this->assertFalse($adapter->has('WRIX/WRIX/WRIST RIGHT/SCOUT 3-PLANE RT. - 2/IM-0001-1001.dcm'));
  }

  public function testArchive () {
    $adapter = new \AdamStipak\Flysystem\Adapter\P7zAdapter(__DIR__ . '/temp/foo.zip');

    $adapter->write('baz/foo.txt', "foo bar", new \League\Flysystem\Config);
    $this->assertTrue($adapter->has('baz/foo.txt'));
    $this->assertEquals('foo bar', $adapter->read('baz/foo.txt'));

    $adapter->delete('baz/foo.txt');
    $this->assertFalse($adapter->has('baz/foo.txt'));

    $adapter->write('baz/foo.txt', "baz", new \League\Flysystem\Config);
    $this->assertEquals('baz', $adapter->read('baz/foo.txt'));
  }
}
