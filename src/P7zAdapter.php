<?php

namespace AdamStipak\Flysystem\Adapter;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Adapter\Polyfill\StreamedCopyTrait;
use League\Flysystem\Adapter\Polyfill\StreamedReadingTrait;
use League\Flysystem\Adapter\Polyfill\StreamedWritingTrait;
use League\Flysystem\Config;
use League\Flysystem\Util;
use Symfony\Component\Process\Process;

class P7zAdapter extends AbstractAdapter {

  use NotSupportingVisibilityTrait;
  use StreamedCopyTrait;
  use StreamedReadingTrait;
  use StreamedWritingTrait;

  /**
   * @var array
   */
  protected static $resultMap = [
    'Modified' => 'mtime',
    'Path'     => 'name',
    'Size'     => 'size',
    'CRC'      => 'crc',
  ];

  /**
   * @var string
   */
  private $location;

  public function __construct (string $location) {
    $this->location = $location;
  }

  public function write ($path, $contents, Config $config) {
    $path = $this->applyPathPrefix($path);
    $tempdir = tempnam(sys_get_temp_dir(), "flysystem-7z");
    if (file_exists($tempdir)) {
      unlink($tempdir);
    }
    mkdir($tempdir);
    if (!is_dir($tempdir)) {
      throw new \LogicException("Cannot create tempdir {$tempdir}");
    }

    $tempfile = $tempdir . "/" . $path;
    mkdir(Util::dirname($tempfile), 0600, true);
    file_put_contents($tempfile, $contents);
    $process = new Process("cd \"{$tempdir}\" && 7z a -tzip \"{$this->location}\" .");
    $this->runProcess($process);

    unlink($tempfile);

    return compact('path', 'contents');
  }

  public function update ($path, $contents, Config $config) {
    $path = $this->applyPathPrefix($path);
    $tempdir = tempnam(sys_get_temp_dir(), "flysystem-7z");
    if (file_exists($tempdir)) {
      unlink($tempdir);
    }
    mkdir($tempdir);
    if (!is_dir($tempdir)) {
      throw new \LogicException("Cannot create tempdir {$tempdir}");
    }

    $tempfile = $tempdir . "/" . $path;
    mkdir(Util::dirname($tempfile), 0600, true);
    file_put_contents($tempfile, $contents);
    $process = new Process("cd \"{$tempdir}\" && 7z u \"{$this->location}\" .");
    $this->runProcess($process);

    unlink($tempfile);

    return compact('path', 'contents');
  }

  public function rename ($path, $newpath) {
    throw new \LogicException('Not supported!');
  }

  public function delete ($path) {
    $path = $this->applyPathPrefix($path);
    $process = new Process("7z d \"{$this->location}\" \"{$path}\"");
    $this->runProcess($process);
  }

  public function deleteDir ($dirname) {
    throw new \LogicException('Not supported!');
  }

  public function createDir ($dirname, Config $config) {
    throw new \LogicException('Not supported!');
  }

  public function has ($path) {
    $path = $this->applyPathPrefix($path);
    $process = new Process("7z l -slt \"{$this->location}\" \"{$path}\"");
    $this->runProcess($process);

    $list = $this->parseListOutput($process->getOutput());

    return count($list) === 1;
  }

  public function read ($path) {
    $path = $this->applyPathPrefix($path);
    $process = new Process("7z e -so \"{$this->location}\" \"{$path}\"");
    $this->runProcess($process);

    return $process->getOutput();
  }

  public function listContents ($directory = '', $recursive = false) {
    $process = new Process("7z l -slt {$this->location}");
    $this->runProcess($process);

    return $this->parseListOutput($process->getOutput());
  }

  public function getMetadata ($path) {
    $path = $this->applyPathPrefix($path);
    $process = new Process("7z l -slt \"{$this->location}\" \"{$path}\"");
    $this->runProcess($process);

    $list = $this->parseListOutput($process->getOutput());

    return $list[0];
  }

  public function getSize ($path) {
    $item = $this->getMetadata($path);

    return $item['size'];
  }

  public function getMimetype ($path) {
    $item = $this->getMetadata($path);

    return Util::guessMimeType($item['name'], $this->read($path));
  }

  public function getTimestamp ($path) {
    $item = $this->getMetadata($path);

    return $item['mtime'];
  }

  private function parseListOutput (string $output) {
    $parts = explode("\n----------", $output);
    $items = explode("\n\n", $parts[1]);
    array_pop($items);

    $items = array_map(function (string $item) {
      return $this->parseItem($item);
    }, $items);

    return $items;
  }

  private function parseItem (string $item) {
    $properties = explode("\n", $item);

    $p = [];

    foreach ($properties as $property) {
      list($key, $value) = explode(" = ", $property);
      $p[$key] = $value;
    }

    $normalized = Util::map($p, self::$resultMap);
    $normalized['mtime'] = strtotime($normalized['mtime']);

    return $normalized;
  }

  private function runProcess (Process $process): void {
    $process->run();
    if (!$process->isSuccessful()) {
      throw new \LogicException("7z error output:" . $process->getErrorOutput(), $process->getExitCode());
    }
  }
}
